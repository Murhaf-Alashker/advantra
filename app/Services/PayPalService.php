<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\TemporaryReservation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPalService
{
    protected ?string $base_url = null;
    protected ?string $client_id = null;
    protected ?string $client_secret = null;
    protected ?string $access_token = null;

    protected $price;

    public function __construct()
    {
        $this->client_id = env('PAYPAL_CLIENT_ID');
        $this->client_secret = env('PAYPAL_CLIENT_SECRET');
        $this->base_url = env('PAYPAL_BASE_URL');
    }

    public function sendPayment(array $allData)
    {
        try {
            $this->checkIds($allData);
            $price = 0;
            foreach ($allData as $data) {
                $model = $this->getType($data['type'])->findOrFail($data['id']);
                $reserved = $this->checkReservationAbility($data,$model);
                if(!$reserved['state']){
                    throw new Exception("Item not available: " . $reserved['message']);
                }
                $price += $reserved['price'];
            }
            $data = $this->formatData($price);

            $response = $this->buildRequest("POST", "/v2/checkout/orders", $data);

            if ($response['success']) {
                $approvalUrl = collect($response['data']['links'])
                    ->firstWhere('rel', 'approve')['href'] ?? null;
                $this->storeTemporaryReservation($allData,$response['data']['id']);
                return [
                    'success' => true,
                    'url' => $approvalUrl,
                    'order_id' => $response['data']['id'] ?? null,
                    'status' => $response['data']['status'] ?? null
                ];
            }
            throw new Exception( __('message.something_went_wrong'));
        } catch (Exception $e) {
            return view('paymentError',['status' => 'error','message' => $e->getMessage()]);

            }

    }

    public function callBack(Request $request)
    {

        try {
            $token = $request->query('token');
            if (!$token) {
                throw new Exception("Token not provided");
            }
            $response = $this->buildRequest("POST", "/v2/checkout/orders/$token/capture");
            if(!$response['success'] || ($response['data']['status'] ?? null) !== "COMPLETED"){
                throw new Exception($token);
            }
            $this->reserve($token);
            //رسالة لليوزر انو تم الحجز بنجاح
            return view('payment',['status' => 'success']);
        } catch (Exception $e) {
            $currentToken = $e->getMessage();
            TemporaryReservation::where('order_id', $currentToken)->delete();
            $refund = $this->refund($response['data']['purchase_units'][0]['payments']['captures'][0]['id'] ?? null,$response['data']['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null);
            if(!$refund){
                //رسالة للادمن
            }
            return view('payment',['status' => 'error','message' => __('messages.something_went_wrong')]);
            //رسالة لليوزر انو صار خطا و رجعو المصاري
        }

    }

    public function cancel(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return view('payment',['status' => 'error','message' => __('messages.something_went_wrong')]);
        }
        TemporaryReservation::where('order_id', $token)->delete();
        //ارسال رسالة بتاكيد الالغاء
        return view('payment',['status' => 'cancel','message' => 'canceled successfully']);

    }

    public function refund($token,$price):bool
    {
        if($price == null || $token == null){
            return false;
        }
        $data = [
            'amount' => [
                'value' => number_format($price, 2, '.', ''),
                'currency_code' => 'USD'
            ],
            'note_to_payer' => 'Refund issued due to request'
        ];
        $response = $this->buildRequest("POST", "/v2/payments/captures/$token/refund", $data);
        if(!$response['success'] || $response['data']['status'] !== "COMPLETED"){
            return false;
        }
        return true;
    }

    private function formatData($value): array
    {
        return [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        'currency_code' => 'USD',
                        'value' => $value
                    ]
                ]
            ],
            "application_context" => [
                "return_url" => route('payment.callBack'),
                "cancel_url" => route('payment.cancel'),
            ]
        ];
    }

    private function buildRequest($method, $url, $data = null , bool $retry = true): array
    {
        try {
            $header = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer " . $this->getAccessToken(),
            ];
            $response = Http::withHeaders($header)
                ->send($method, $this->base_url . $url, $data ? ['json' => $data] : []);
            if($response->status() === 401 && $retry){
                $this->access_token = null;
                return $this->buildRequest($method, $url, $data, false);
            }
            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'data'    => $response->json()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'status'  => 500,
                'message' => $e->getMessage()
            ];
        }
    }

    private function getAccessToken(): string
    {
        if ($this->access_token) {
            return $this->access_token;
        }

        $response = Http::asForm()->withBasicAuth($this->client_id, $this->client_secret)
            ->post($this->base_url . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

        if (!$response->successful()) {
            throw new Exception('Unable to get PayPal access token');
        }

        $this->access_token = $response->json()['access_token'];
        return $this->access_token;
    }

    private function getType($type)
    {
        return match ($type) {
            'guide' => Guide::query(),
            'group_trip' => GroupTrip::query(),
            'event' => Event::query(),
            default => throw new Exception("Unsupported type: $type")
        };
    }

    private function checkReservationAbility(array $data, $model):array
    {
        if($model instanceof Guide) {
            $date = Carbon::parse($data['date']);
            $task = $model->tasks()
                            ->where('start_date','<=',$date)
                            ->where('end_date','>=',$date)
                            ->first();
            if($task) {
                return ['state'=> false, 'message'=>__('messages.has_reserved')];
            }
            elseif (Carbon::parse($date)->lessThan(Carbon::now())) {
                return ['state'=> false, 'message'=>__('messages.invalid_date')];
            }

            return ['state'=> true,'price' => $model->price];
        }
        elseif($model instanceof GroupTrip) {

            if($model->remaining_tickets == 0){
                return ['state'=> false, 'message'=>__('messages.out_of_tickets')];
            }
            elseif($model->status != Status::PENDING){
                return ['state' => false, 'message' => __('messages.unavailable',[__('attributes.group_trip')])];
            }
            if($model->remaining_tickets >= $data['tickets_count']){
                $price = $model->hasOffer()
                    ? round($model->price * ((100 - $model->offers()->first()->discount) / 100) * $data['tickets_count'])
                    : $model->price * $data['tickets_count'];
                return ['state'=> true, 'price' => $price];
            }
            return ['state'=> false, 'message'=>__('messages.less_tickets')];
        }
        else{
            if($model->isEnded()){
                return ['state'=> false, 'message'=>__('messages.has_ended')];
            }
            else if($model->status != 'active'){
                return ['state' => false, 'message' => __('messages.unavailable',[__('attributes.event')])];
            }
            elseif($model->isLimited()){
                $limit = $model->limitedEvents()->where('remaining_tickets', '>', 0)->first();
                if(!$limit){
                    return ['state'=> false, 'message'=>__('messages.out_of_tickets')];
                }

                if($limit->remaining_tickets >= $data['tickets_count']){
                    $price = $model->hasOffer()
                        ? round($model->price * ((100 - $model->offers()->first()->discount) / 100) * $data['tickets_count'])
                        : $model->price * $data['tickets_count'];
                    return ['state'=> true, 'price' => $price];
            }
                else{
                    return ['state' => false, 'message' =>__('messages.less_tickets')];
                }
            }
            $price = $model->hasOffer()
                ? round($model->price * ((100 - $model->offers()->first()->discount) / 100) * $data['tickets_count'])
                : $model->price * $data['tickets_count'];
            return ['state'=> true, 'price' => $price];
        }
    }

    private function storeTemporaryReservation(array $allData, $order_id):void
    {
        $user_id = Auth::guard('api-user')->user()->id;
        foreach ($allData as $key => $data) {
            TemporaryReservation::create([
                'user_id' => $user_id,
                'order_id' => $order_id,
                'model' => $data['type'],
                'model_id' => $data['id'],
                'tickets_count' => $data['tickets_count'] ?? null,
                'task_date' => $data['date'] ?? null,
            ]);
        }
    }

    private function checkIds(array $allData):void
    {
        $types = ['guide','group_trip','event'];

        foreach ($types as $type) {
            $ids = collect($allData)
                ->where('type', $type)
                ->pluck('id')
                ->all();

            if (empty($ids)) {
                continue;
            }

            $ids = array_values(array_unique($ids));

            $table = Str::of($type)->finish('s');
            $existingIds = DB::table($table)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all();

            $invalid = collect($ids)->diff($existingIds);
            if (!$invalid->isEmpty()) {
                throw new Exception("Invalid {$type} ids: " . $invalid->join(', '));
            }
        }

    }

    private function reserve(string $token):void
    {
        DB::transaction(function () use ($token) {
            $reservingItems = TemporaryReservation::where('order_id', $token)->get();
            foreach ($reservingItems as $item) {
                $model = $this->getType($item->model)->lockForUpdate()->findOrFail($item->model_id);
                if($model instanceof Guide){
                    $hasTask = $model->tasks()
                                     ->where('start_date','<=',$item->task_date)
                                     ->where('end_date','>=',$item->task_date)
                                     ->first();
                    if($hasTask)
                    {
                        throw new Exception($token);
                    }
                    $start_date = Carbon::parse($item->task_date)->startOfDay();
                    $end_date = Carbon::parse($item->task_date)->endOfDay();
                    $model->tasks()->create([
                        'taskable_id' => $item->user_id,
                        'taskable_type' => User::class,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                    ]);
                    $item->delete();
                }
                elseif ($model instanceof GroupTrip){
                    if($model->remaining_tickets < $item->tickets_count){
                        throw new Exception($token);
                    }
                    $model->reservations()->create([
                        'tickets_count' => $item->tickets_count,
                        'ticket_price' => $model->hasOffer()
                                                ? round($model->price * ((100 - $model->offers()->first()->discount) / 100))
                                                : $model->price,
                        'basic_cost' => $model->basic_cost,
                        'expire_date' => $model->ending_date,
                        'user_id' => $item->user_id,
                    ]);
                    $model->remaining_tickets = $model->remaining_tickets - $item->tickets_count;
                    $model->save();
                    $item->delete();
                }
                else{
                    if($model->isLimited()) {
                        $limit = $model->limitedEvents()->lockForUpdate()->first();
                        if ($limit->remaining_tickets < $item->tickets_count) {
                            throw new Exception($token);
                        }
                        $limit->remaining_tickets = $limit->remaining_tickets - $item->tickets_count;
                        $limit->save();
                    }
                    $model->reservations()->create([
                        'tickets_count' => $item->tickets_count,
                        'ticket_price' => $model->hasOffer()
                            ? round($model->price * ((100 - $model->offers()->first()->discount) / 100))
                            : $model->price,
                        'basic_cost' => $model->basic_cost,
                        'expire_date' => $model->ending_date,
                        'user_id' => $item->user_id,
                    ]);
                    $item->delete();
                }
            }

        });
    }
}
