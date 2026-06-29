<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Domains\Order\Models\Order;
use App\Infrastructure\Clients\UserClient;

class OrderController extends Controller
{
    protected UserClient $userClient;

    public function __construct(UserClient $userClient)
    {
        $this->userClient = $userClient;
    }

    public function index()
    {
        $orders = [];
        try {
            $orders = Order::all()->toArray();
            if (empty($orders)) {
                $orders = Order::getMockOrders();
            }
        } catch (\Exception $e) {
            $orders = Order::getMockOrders();
        }

        foreach ($orders as &$order) {
            $userId = $order['user_id'];
            $user = $this->userClient->getUser($userId);
            $order['user'] = $user ?: ['id' => $userId, 'name' => 'Unknown User (Offline)'];
        }

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = null;
        try {
            $order = Order::find($id);
            if ($order) {
                $order = $order->toArray();
            }
        } catch (\Exception $e) {}

        if (!$order) {
            $mocks = Order::getMockOrders();
            foreach ($mocks as $m) {
                if ($m['id'] == $id) {
                    $order = $m;
                    break;
                }
            }
        }

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $userId = $order['user_id'];
        $user = $this->userClient->getUser($userId);
        $order['user'] = $user ?: ['id' => $userId, 'name' => 'Unknown User (Offline)'];

        return response()->json($order);
    }
}
