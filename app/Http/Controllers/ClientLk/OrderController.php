<?php

namespace App\Http\Controllers\ClientLk;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ClientOrder\CashBackCalculationService;
use App\Services\ClientOrder\ClientOrderService;
use App\Services\ClientOrder\OrderFinanceService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private ClientOrderService         $clientOrderService;
    private OrderFinanceService        $orderFinanceService;
    private CashBackCalculationService $cashBackCalculationService;

    /**
     * @param ClientOrderService $clientOrderService
     * @param OrderFinanceService $orderFinanceService
     * @param CashBackCalculationService $cashBackCalculationService
     */
    public function __construct(ClientOrderService $clientOrderService, OrderFinanceService $orderFinanceService, CashBackCalculationService $cashBackCalculationService)
    {
        $this->clientOrderService = $clientOrderService;
        $this->orderFinanceService = $orderFinanceService;
        $this->cashBackCalculationService = $cashBackCalculationService;
    }

    /**
     * Получаем параметры заказа, цены и все возможные скидки
     * @param Request $request
     * @return bool|JsonResponse
     * @throws Exception
     */
    public function index(Request $request): bool|JsonResponse
    {
        $order = Order::where(['id' => $request->orderId, 'user_id' => $request->userId])->firstOrFail();
        $pay = $request->pay ?? false;
        $baseData = $this->clientOrderService->getOrderBaseData($order, $request->userId, $pay);
        $this->clientOrderService->getOrderInfo($order, $request->userId, $baseData);

        return response()->json($baseData);
    }

    /**
     * Получаем список заказов
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function orderList(Request $request)
    {
        $where = [
            ['user_id', '=', $request->userId],
            ['active', '=', 1]
        ];
        $list = Order::where($where)->orderBy('create', 'desc');
        if ($request->status) {
            $statuses = explode('||', $request->status);
            $list->whereIn('status', $statuses);
        }
        $list = $list->get();
        $orderList = [];
        foreach ($list as $item) {
            $orderList[] = $this->clientOrderService->getOrderBaseData($item, $request->userId);
        }
        $orderStatuses = $this->clientOrderService->getOrderCounters($request->userId);
        return response()->json(['list' => $orderList, 'statuses' => $orderStatuses]);
    }

    /**
     * Рассчет цен заказа в зависимости от применённых бонусов и промокодов
     * @param Request $request
     * @return JsonResponse
     * @throws Exception|GuzzleException
     */
    public function changeOrderPrice(Request $request): JsonResponse
    {
        $order = Order::where('id', '=', $request->orderId)->firstOrFail();
        if ($request->fullPaymentFlag) {
            $orderPrice = $this->clientOrderService->getOrderDiscount($order, $request, true);
        } else {
            $orderPrice = $this->clientOrderService->getOrderDiscount($order, $request);
        }

        return response()->json($orderPrice);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function orderBonusesPayment(Request $request): JsonResponse
    {
        $result = $this->orderFinanceService->appendOrderBonusesPayment($request);
        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function orderPayment(Request $request): JsonResponse
    {
        $result = $this->orderFinanceService->appendOrderPayment($request);
        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkUserCashBack(Request $request): JsonResponse
    {
        $result = $this->cashBackCalculationService->checkUserCashBackPercent($request->userId);

        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function setUserCashBack(Request $request): JsonResponse
    {
        $result = $this->cashBackCalculationService->checkUserCashBackPercent($request->userId, $request->orderId, true);

        return response()->json($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function returnCashBack(Request $request): JsonResponse
    {
        $this->orderFinanceService->returnOrderCashBack($request->orderId, $request->orderReturnSum, $request->userId);
        return response()->json([]);
    }

    public function checkOrderCashBackSize(Request $request): JsonResponse
    {
        $order = Order::where('id', '=', $request->orderId)->firstOrFail();
        $result = $this->clientOrderService->checkOrderCashBackSize($order, null);

        return response()->json(['cashBack' => $result]);
    }

    public function getSaleScale(Request $request): JsonResponse
    {
        $result = $this->cashBackCalculationService->getSaleScale($request->ordersTotal, $request->userId);

        return response()->json($result);
    }

}
