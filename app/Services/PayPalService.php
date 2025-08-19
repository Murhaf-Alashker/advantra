<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\SoloTrip;
use App\Models\TemporaryReservation;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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

    public function pay(array $allData,string $type):JsonResponse
    {
        if($type == 'points'){
            return $this->payUsingPoints($allData);
        }
        return $this->sendPayment($allData);
    }

    public function sendPayment(array $allData):JsonResponse
    {
        try {
            $this->checkIds($allData);
            $price = 0;
            foreach ($allData as $data) {
                $model = $this->getType($data['type'])->findOrFail($data['id']);
                $reserved = $this->checkReservationAbility($data,$model);
                if(!$reserved['state']){
                    return response()->json("Item not available: " . $reserved['message'],400);
                }
                $price += $reserved['price'];
            }
            $data = $this->formatData($price);

            $response = $this->buildRequest("POST", "/v2/checkout/orders", $data);

            if ($response['success']) {
                $approval = collect($response['data']['links'] ?? [])->firstWhere('rel', 'approve');
                $approvalUrl = data_get($approval, 'href');

                $this->storeTemporaryReservation($allData,$response['data']['id']);
                $info = [
                    'success' => true,
                    'url' => $approvalUrl,
                    'order_id' => $response['data']['id'] ?? null,
                    'status' => $response['data']['status'] ?? null
                ];
                return response()->json($info);
            }
            throw new Exception( __('message.something_went_wrong'));
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);

            }

    }

    public function callBack(Request $request):mixed
    {
        return DB::transaction(function () use ($request) {;
            try {
                $token = $request->query('token');
                if (!$token) {
                    throw new Exception("Token not provided");
                }

                $response = $this->buildRequest("POST", "/v2/checkout/orders/$token/capture");
                if(!$response['success'] || ($response['data']['status'] ?? null) !== "COMPLETED"){
                    throw new Exception($response['message'],500);
                }

                $eventsIds = $this->reserve($token);
                if(sizeof($eventsIds['events']) > 1){
                    $this->createSoloTrip($eventsIds);
                }
                //رسالة لليوزر انو تم الحجز بنجاح
                return view('payment',['status' => 'success','message' => 'afkihfsfbf']);
            } catch (Exception $e) {
                $currentMessage = $e->getMessage();
                TemporaryReservation::where('order_id', $token)->delete();
                if ($e->getCode() == 400) {

                    $refund = $this->refund($response['data']['purchase_units'][0]['payments']['captures'][0]['id'] ?? null, $response['data']['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null);

                if (!$refund) {
                    return view('payment', ['status' => 'cancel', 'message' => $currentMessage]);
                    //رسالة للادمن قث
                }
            }
                return view('payment',['status' => 'error','message' => $currentMessage]);
                //رسالة لليوزر انو صار خطا و رجعو المصاري
            }
        });

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

    public function payUsingPoints(array $allData):JsonResponse
    {
        if(sizeof($allData) > 1){
            return response()->json([
                'message' => 'you can pay using points for only one item'
            ],400);
        }
        $user = Auth::guard('api-user')->user();

        $data = $allData[0];

        $model = $this->getType($data['type'])->lockForUpdate()->findOrFail($data['id']);

        $reserved = $this->checkReservationAbility($data,$model);

        if(!$reserved['state']){
            return response()->json("Item not available: " . $reserved['message'],400);
        }
        elseif ($reserved['price'] * 10 > $user->points){
            return response()->json([
                'message' => 'you do not have enough points to pay'
            ],400);
        }
        else{
            $model->price = 0;
            $user->decrement('points', $reserved['price'] * 10);

            $info = [
                'user_id' => $user->id,
                'model' => $data['type'],
                'model_id' => $data['id'],
                'tickets_count' => $data['tickets_count'] ?? null,
                'task_date' => $data['date'] ?? null,
            ];
            $this->toDatabase((object)$info, $model);
            //رسالة لليوزر انو تمت عملية الدفع
            return response()->json([
                'message' => 'payment successful',
            ]);
        }

    }

    private function formatData($value): array
    {
        return [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "amount" => [
                        'currency_code' => 'USD',
                        'value' => number_format($value, 2, '.', '')
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

    private function getType($type):Builder
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
                return ['state'=> false, 'message'=>__('messages.guide.has_reserved')];
            }
            elseif (Carbon::parse($date)->lessThan(Carbon::now())) {
                return ['state'=> false, 'message'=>__('messages.guide.invalid_date')];
            }

            return ['state'=> true,'price' => $model->price];
        }
        elseif($model instanceof GroupTrip) {

            if($model->remaining_tickets == 0){
                return ['state'=> false, 'message'=>__('messages.group.out_of_tickets')];
            }
            elseif($model->status != Status::PENDING->value){

                return ['state' => false, 'message' => __('messages.group.unavailable' ,[__('attributes.group_trip')])];
            }
            if($model->remaining_tickets >= $data['tickets_count']){
                $price = $model->hasOffer()
                    ? round($model->price * ((100 - $model->offers()->first()->discount) / 100) * $data['tickets_count'])
                    : $model->price * $data['tickets_count'];
                return ['state'=> true, 'price' => $price];
            }
            return ['state'=> false, 'message'=>__('messages.group.less_tickets')];
        }
        else{
            if($model->isEnded()){
                return ['state'=> false, 'message'=>__('messages.event.has_ended')];
            }
            else if($model->status != 'active'){
                return ['state' => false, 'message' => __('messages.event.unavailable',[__('attributes.event')])];
            }
            elseif($model->isLimited()){
                $limit = $model->limitedEvents()->where('remaining_tickets', '>', 0)->first();
                if(!$limit){
                    return ['state'=> false, 'message'=>__('messages.event.out_of_tickets')];
                }

                if($limit->remaining_tickets >= $data['tickets_count']){
                    $price = $model->hasOffer()
                        ? round($model->price * ((100 - $model->offers()->first()->discount) / 100) * $data['tickets_count'])
                        : $model->price * $data['tickets_count'];
                    return ['state'=> true, 'price' => $price];
            }
                else{
                    return ['state' => false, 'message' =>__('messages.event.less_tickets')];
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

    private function reserve(string $token)
    {
        return DB::transaction(function () use ($token) {

            $reservingItems = TemporaryReservation::where('order_id', $token)->get() ?? [];

            $info = [
                'user_id' => null,
                'events' => [],
            ];

            foreach ($reservingItems as $item) {

                $model = $this->getType($item->model)->lockForUpdate()->findOrFail($item->model_id);

                $info['user_id'] = $item->user_id;

                $price = $this->toDatabase($item,$model,$token);
                if($model instanceof Event) {
                    $info['events'][] = ['event_id' =>$model->id , 'price'=> $price ,'tickets_count' => $item->tickets_count];
                }
                $item->delete();
            }
            return $info;
        });
    }

    public function createSoloTrip(array $allData):void
    {
        $soloTrip = SoloTrip::create([
            'user_id' => $allData['user_id'],
        ]);
        $sync = [];
        $price = 0;
        foreach ($allData['events'] as $event) {
            $sync[$event['event_id']] = ['price'=>$event['price'],'tickets_count' => $event['tickets_count']];
            $price += $event['price'] * $event['tickets_count'];
        }

        $soloTrip->events()->sync($sync);
        $soloTrip->total_price = $price;
        $soloTrip->save();
    }

    private function toDatabase($item,$model,$token = 'payment_using_points')
    {
        if($model instanceof Guide){
            $hasTask = $model->tasks()
                ->where('start_date','<=',$item->task_date)
                ->where('end_date','>=',$item->task_date)
                ->first();
            if($hasTask)
            {
                throw new Exception($token,400);
            }
            $start_date = Carbon::parse($item->task_date)->startOfDay();
            $end_date = Carbon::parse($item->task_date)->endOfDay();
            $model->tasks()->create([
                'taskable_id' => $item->user_id,
                'taskable_type' => User::class,
                'start_date' => $start_date,
                'end_date' => $end_date,
            ]);
            return $model->price;
        }
        elseif ($model instanceof GroupTrip){
            if($model->remaining_tickets < $item->tickets_count){
                throw new Exception($token,400);
            }
            $price = $model->hasOffer()
                ? round($model->price * ((100 - $model->offers()->first()->discount) / 100))
                : $model->price;
            $model->reservations()->create([
                'tickets_count' => $item->tickets_count,
                'ticket_price' => $price,
                'basic_cost' => $model->basic_cost,
                'expire_date' => $model->ending_date,
                'user_id' => $item->user_id,
            ]);
            $model->decrement('remaining_tickets',$item->tickets_count);
//            $model->remaining_tickets = $model->remaining_tickets - $item->tickets_count;
//            $model->save();
            return $price;
        }
        else{
            if($model->isLimited()) {
                $limit = $model->limitedEvents()->lockForUpdate()->first();
                if ($limit->remaining_tickets < $item->tickets_count) {
                    throw new Exception($token,400);
                }
                $limit->decrement('remaining_tickets',$item->tickets_count);
//                $limit->remaining_tickets = $limit->remaining_tickets - $item->tickets_count;
//                $limit->save();
            }
            $price = $model->hasOffer()
                ? round($model->price * ((100 - $model->offers()->first()->discount) / 100))
                : $model->price;
            $model->reservations()->create([
                'tickets_count' => $item->tickets_count,
                'ticket_price' => $price,
                'basic_cost' => $model->basic_cost,
                'expire_date' => $model->ending_date,
                'user_id' => $item->user_id,
            ]);
            return $price;
        }
    }
}
