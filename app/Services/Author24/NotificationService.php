<?php

namespace App\Services\Author24;

use JetBrains\PhpStorm\ArrayShape;

class NotificationService
{
    private array $notification;
    public const TYPE_ORDER_EDIT = 1;
    public const TYPE_DATE_CHANGE = 2;
    public const TYPE_CHECKED_AUTHOR = 3;
    public const TYPE_PRICE_CHANGE_REQUEST = 4;

    private int     $type;
    private ?string $ordersLog;
    private bool    $sendHistoryChange   = false;
    private bool    $correctNotification = true;


    /**
     * @param array $notification
     */
    public function __construct(array $notification)
    {
        $this->notification = $notification;
    }

    /**
     * @return $this
     */
    public function prepareNotification(): NotificationService
    {
        switch ($this->notification['message']) {
            case 'Заказчик отредактировал заказ':
                $this->type = self::TYPE_ORDER_EDIT;
                $this->ordersLog = 'author24.order.edit';
                $this->sendHistoryChange = true;
                break;
            case 'Заказчик изменил дату сдачи заказа':
                $this->type = self::TYPE_DATE_CHANGE;
                $this->ordersLog = 'author24.order.edit';
                $this->sendHistoryChange = true;
                break;
            case 'Заказчик запросил перерасчет по заказу':
                $this->type = self::TYPE_PRICE_CHANGE_REQUEST;
                $this->ordersLog = 'author24.notify';
                break;
            default:
                $this->correctNotification = false;
                break;
        }

        return $this;

    }

    /**
     * @return array
     */
    #[ArrayShape(['id'               => "mixed", 'message' => "mixed", 'order_id' => "mixed", 'our_type' => "int",
                  'orders_log_event' => "null|string", 'send_to_history' => "bool", 'is_read' => "mixed"])]
    public function toArray(): array
    {
        return [
            'id'               => $this->notification['id'],
            'message'          => $this->notification['message'],
            'order_id'         => $this->notification['orderId'],
            'our_type'         => $this->type,
            'orders_log_event' => $this->ordersLog,
            'send_to_history'  => $this->sendHistoryChange,
            'is_read'          => $this->notification['isRead']
        ];
    }

    /**
     * @return bool
     */
    public function isCorrect(): bool
    {
        return $this->correctNotification;
    }
}
