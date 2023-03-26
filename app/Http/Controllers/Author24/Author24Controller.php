<?php

namespace App\Http\Controllers\Author24;

use App\Http\Controllers\Controller;
use App\Models\Author24\Account;
use App\Models\Author24\Order;
use App\Models\Files\Files;
use App\Services\Author24\Author24Service;
use Illuminate\Http\Request;

class Author24Controller extends Controller
{
    public function getDictionary(Request $request)
    {
        $account = Account::first();
        $service = new Author24Service($account);
        $type = $request->get('type');

        switch ($type) {
            case 'worktypes':
                return response()->json($service->getWorkTypesDictionary());
            case 'workcategories':
                return response()->json($service->getWorkCategoriesDictionary());
        }
        abort(403);
    }

    public function getNewOrdersWithOutOffer(Request $request)
    {
        $accountEmail = $request->get('email');
        $account = Account::where('email', $accountEmail)->firstOrFail();
        $service = new Author24Service($account);
        return response()->json($service->getNewOrdersWithOutOffer($request->get('page') ?? 0));
    }

    public function getPerformerOrders(Request $request)
    {
        $accountEmail = $request->get('email');
        $account = Account::where('email', $accountEmail)->firstOrFail();
        $service = new Author24Service($account);
        return response()->json($service->getPerformerOrders($request->get('limit'), $request->get('offset')));
    }

    public function getDialog($id, Request $request)
    {
        $order = Order::with('account')->findOrFail($id);
        $service = new Author24Service($order->account);
        return response()->json($service->getOrderDialog($order));
    }
    public function getOrder(int $id, Request $request)
    {
        $accountId = $request->get('account_id');
        $account = Account::find($accountId);
        $service = new Author24Service($account);
        return response()->json($service->getOrderById($id));
    }

    public function sendMessage(Request $request)
    {
        $data = $request->all();
        $order = Order::with('account')->findOrFail($data['order_id']);
        $service = new Author24Service($order->account);
        return response()->json($service->sendMessageToChat($order, $data['text']));
    }

    public function sendMessageWithFile(Request $request)
    {
        $data = $request->all();
        $order = Order::with('account')->findOrFail($data['order_id']);
        $file = Files::findOrFail($data['file_id']);
        $service = new Author24Service($order->account);
        try {
           return $service->sendFileToChat($order, $file, $data['status']);
        } catch (\Throwable $e) {
            \Log::stack(['author24'])->error('File send error', [$e->getCode(), $e->getMessage()]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function acceptWork(Request $request)
    {
        $data = $request->all();
        $order = Order::with('account')->findOrFail($data['order_id']);
        $service = new Author24Service($order->account);
        return response()->json($service->acceptWork($order));

    }

    public function rejectWork(Request $request)
    {
        $data = $request->all();
        $order = Order::with('account')->findOrFail($data['order_id']);
        $service = new Author24Service($order->account);
        return response()->json($service->rejectWork($order));

    }

    public function setNewPrice(Request $request)
    {
        $data = $request->all();
        $order = Order::with('account')->findOrFail($data['order_id']);
        $service = new Author24Service($order->account);
        return response()->json($service->setOrderBid($order, false));
    }
}
