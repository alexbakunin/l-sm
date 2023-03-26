<?php

namespace App\Services\ClientOrder;

use App\Models\CrmUser;
use App\Models\Files\Files;
use App\Models\Order;
use App\Models\OrderCashBack;
use App\Models\OrderCourse;
use App\Models\OrderFinances;
use App\Models\OrderFontInterval;
use App\Models\OrderFontSize;
use App\Models\OrderOriginalityCheck;
use App\Models\OrderStatus;
use App\Models\OrderTypeOfWork;
use App\Models\Promocode;
use App\Models\PromocodeSettings;
use App\Services\ClientOrder\Repositories\ClientOrderRepository;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;

class ClientOrderService
{
    private ?Promocode                   $promoCode;
    private ?PromocodeSettings           $promoCodeSettings;
    protected ClientOrderRepository      $repository;
    protected CashBackCalculationService $cashBackCalculationService;
    private int                          $cashBackReleaseDate;
    private string                       $userSource;

    const WIZARD_SOURCE = ['a24m', 'a24g', 'a24_3'];

    const ORDER_STATUS_CLASSES = [
        'issued'                => 'ready',
        'search_author'         => 'price',
        'running'               => 'price',
        'executed'              => 'price',
        'executed_awaiting_pay' => 'price',
        'assessment'            => 'price',
        'request'               => 'price',
        'auto_estimated'        => 'price',
        'new'                   => 'new',
        'waiting_information'   => 'new',
        'adjustment'            => 'new',
        'auto_estimated_paid'   => 'price',
        'return'                => 'return',
    ];

    const ORDER_STATUS_LIST = [
        'all'                   => ['label' => 'Все (#count#)', 'statuses' => ''],
        'new'                   => ['label' => 'На согласовании (#count#)', 'statuses' => 'new||waiting_information'],
        'assessment'            => ['label' => 'Не оплачен (#count#)', 'statuses' => 'assessment||auto_estimated'],
        'running'               => [
            'label' => 'В работе (#count#)', 'statuses' => 'search_author||running||auto_estimated_paid'
        ],
        'executed_awaiting_pay' => [
            'label' => 'Завершен и ожидает оплаты (#count#)', 'statuses' => 'executed_awaiting_pay'
        ],
        'issued'                => ['label' => 'Завершен (#count#)', 'statuses' => 'issued||executed'],
        'adjustment'            => ['label' => 'В корректировке (#count#)', 'statuses' => 'adjustment'],
        'archive'               => ['label' => 'Архивный (#count#)', 'statuses' => 'archive'],
    ];

    const DEFAULT_CASH_BACK_RELEASE_DATE = '11.08.2022 00:00:00';

    /**
     * @param ClientOrderRepository $repository
     * @param CashBackCalculationService $cashBackCalculationService
     */
    public function __construct(ClientOrderRepository $repository, CashBackCalculationService $cashBackCalculationService)
    {
        $this->repository = $repository;
        $this->cashBackCalculationService = $cashBackCalculationService;
        $cashBackModuleReleaseDate = $this->repository->getCrmSettings('cash_back_release_date')?->value
            ?: self::DEFAULT_CASH_BACK_RELEASE_DATE;
        $this->cashBackReleaseDate = strtotime($cashBackModuleReleaseDate);
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getOrderCounters(int $userId): array
    {
        $list = [];
        $statusList = self::ORDER_STATUS_LIST;
        $statusCounters = $this->repository->getStatusesCounters($userId);
        foreach ($statusList as $key => &$value) {
            $value['label'] = str_replace('#count#', $statusCounters->{$key}, $value['label']);
            if ($statusCounters->{$key} != 0) {
                $list[$value['statuses']] = $value['label'];
            }
        }

        return $list;
    }

    /**
     * Получаем основную информацию по заказу
     * @param Order $order
     * @param int $userId
     * @param bool $payment
     * @return array
     * @throws Exception
     */
    public function getOrderBaseData(Order $order, int $userId, bool $payment = false): array
    {

        $this->checkPromocodeDaysLeft($order);
        $this->checkPromocodeFirstOrder($order);
        $contracts = $this->getOrderContract($order);
        $data = $this->cashBackCalculationService->checkUserCashBackPercent($order->user_id);
        $this->userSource = $data['userSource'];
        if ($order->blocked) {
            $this->repository->removeOrderBlock($order->id);
        }
        $orderCreateDate = Carbon::parse(date('d-m-Y', $order->create))->locale('ru');
        $this->promoCode = $order->promocode;
        $orderDeadline = false;
        if ($order->deadline) { // Если в заказе установлен дедлайн, то выведем человеко понятную дату и сколько осталось времени
            $orderDeadline = \NumberHelper::numberTranslation($order->deadline);
            if ($order->date_start) {
                $dateEnd = $order->deadline * 24 * 60 * 60 + $order->date_start;
                $deadLineDate = Carbon::parse($dateEnd)->locale('ru');
                $orderDeadline .= ' (' . $deadLineDate->day . ' ' . $deadLineDate->getTranslatedMonthName('Do MMMM') . ' ' . $deadLineDate->year . ')';
            }

        }
        return [
            'id'                 => $order->id,
            'blocked'            => $order->blocked,
            'status'             => $order->status,
            'source'             => $order->source,
            'statusClass'        => self::ORDER_STATUS_CLASSES[$order->status] ?? 'new',
            'statusCode'         => $this->getOrderStatus($order),
            'theme'              => $order->theme ?? '',
            'typeOfWork'         => $this->getOrderTypeOfWork($order->type_of_work),
            'course'             => $this->getOrderCourse($order->course_id),
            'fontSize'           => $this->getOrderFontSize($order->font_size),
            'fontInterval'       => $this->getOrderFontInterval($order->font_interval),
            'pages'              => $order->pages_count,
            'originalityPercent' => $order->originality_proc,
            'originalityCheck'   => $this->getOrderOriginalityCheck($order->originality),
            'clientDescription'  => $order->note,
            'createDate'         => $orderCreateDate->day . ' ' . $orderCreateDate->getTranslatedMonthName('Do MMMM') . ' ' . $orderCreateDate->year,
            'deadLine'           => $orderDeadline,
            'finances'           => $this->getOrderFinanceData($order, $userId, $payment),
            'newChatMessages'    => $this->repository->getChatNewMassages($order, $userId),
            'office_id'          => $order->office_id,
            'contract_id'        => $order->contract_id,
            'contracts'          => $contracts ?? false,
        ];
    }

    /**
     * Получаем финансовую информацию по заказу
     * @param Order $order
     * @param int $userId
     * @param bool $payment
     * @return array|null
     * @throws Exception
     */
    public function getOrderFinanceData(Order $order, int $userId, bool $payment): ?array
    {
        $this->getOrderPriceChanges($order);    // Изменения стоимости заказа, если они есть
        $orderSalePrices = $this->getOrderPricesForSale($order);
        $priceForCashBack = $orderSalePrices['priceForCashBack'];
        $priceForSale = $order->priceForSale = $orderSalePrices['priceForSale'];
        $orderPercent = ceil($order->priceForSale * 0.4); // Вычисляем 40% от стоимости заказа
        $this->promoCode = $order->promocode ?? NULL;
        $this->promoCodeSettings = $this->promoCode->settings ?? NULL;
        $user = CrmUser::find($userId);
        /** Получаем размеры виртуальных бонусов и виртуальных промокод-бонусов */
        $virtualBonus = $this->getOrderVirtualDiscount($order, $userId);
        if ($virtualBonus < $orderPercent) { // Если виртуальные бонусы меньше 40% от стоимости заказа
            $diff = $orderPercent - $virtualBonus; // Вычислим разницу между 40% от заказа и суммой виртуальных бонусов
            $virtualBonusPromo = min($this->getOrderVirtualDiscount($order, $userId, true), $diff);  // Берем минимальное значение между возможными виртуальными промо бонусами и разницей, вычисленной на предыдущем шаге
        } else {
            $virtualBonusPromo = 0;
        }
        $promoCode = $this->getOrderPromoCodeData($order); // Получаем данные промокода
        $order->prepayPrice = round(($order->priceForSale / 100) * $order->prepay);
        $order->payed = $this->getOrderPayed($order);
        /** Вычисляем размер оплаченной части в процентах */
        if ($order->payed) {
            if ($order->priceForSale || $order->source == 'myknow') {
                $order->payedPercent = round($order->payed / ($order->priceForSale / 100));
            } else {
                $order->payedPercent = $order->price !== 0 ? (round($order->payed / ($order->price / 100))) : 0;
            }
        }
        /** Вычисляем размер доплаты и максимально возможное значение партнерских бонусов для списания в заказе
         * в зависимости от того предоплата это или уже доплата до полной стоимости заказа */
        if ($order->source == 'myknow') {
            $order->surcharge = $maxPartner = 0;
        } else {
            $order->surcharge = $maxPartner = $order->payed >= $order->prepayPrice
                // Если оплачено больше размера предоплаты
                ? ($order->priceForSale// Проверим модифицирована ли цена бонусами и скидками
                    ? $order->priceForSale - $order->payed// Если модифицирована, то из неё вычитаем внесенную оплату
                    : $order->price - $order->payed)// Иначе берем разницу между полной стоимостью и внесенной оплатой
                : $order->prepayPrice - $order->payed;                              // Если внесенная оплата меньше предоплаты, тогда берем их разницу для доплаты
        }
        /** Сравниваем максимально возможное значение для списания партнерских бонусов
         * и баланс самих бонусов у клиента, и берем меньшее из двух значений */
        $order->maxPartner = min((int)$maxPartner, (int)$user->balance);

        /** Если у настройках промокода установлен кешбэк, то берем его значение в %, иначе false */
        $promocodeCashBack = ($this->promoCodeSettings && $this->promoCodeSettings->cashback_check)
            ? $this->promoCodeSettings->cashback : false;
        /** Если есть кешбек от промокода, используем его
         * Иначе проверяем размер % кешбэка пользователя и если он равен 0, то принудительно выставляем кешбэк равный 3% */
        $cashBackPercent = ($promocodeCashBack ? (int)$promocodeCashBack
            : ($order->cash_back_percent ? (int)$order->cash_back_percent : ($user->sale > 0 ? (int)$user->sale : 3)));

        /** Блокировка применения кешбэка для старых заказов, ранее указанной в настройках даты */
        /** Блокировка применения кешбэка для клиентов с источником Wizard24 */
        if ($order->create > $this->cashBackReleaseDate || in_array($this->userSource, self::WIZARD_SOURCE)) {
            if (($order->payed < $order->priceForSale && !$payment) || $order->payed == 0) {
                $order->cashBack = $this->checkOrderCashBackSize($order, $cashBackPercent);
            } else {
                $cashBackFromOrder = OrderCashBack::find($order->cash_back_id);
                $order->cashBack = $cashBackFromOrder->value ?? 0;
            }
            $this->setCashBackForOrder($order, $user, $order->cashBack);
            $order->sale = false;
        } else {
            $order->cashBack = false;
        }
        return [
            'orderCashBackPercent' => $order->sale,
            'bonusesSale'          => $orderSalePrices['bonusSale'],
            'bonusOutDate'         => $orderSalePrices['bonusOutDate'],
            'price'                => $order->price,
            'priceChange'          => $order->priceChange,
            'priceForSale'         => $order->priceForSale,
            'cashBack'             => $order->cashBack,
            'prepay'               => $order->prepayPrice,
            'payed'                => $order->payed,
            'payedPercent'         => $order->payedPercent,
            'surcharge'            => $order->surcharge, //доплата
            'promoCode'            => $promoCode ?? false,
            'promoCodeSale'        => $promoCode ? $promoCode['sale'] : false,
            'bonuses'              => $virtualBonus,
            'bonusesCheck'         => $order->paybonuses && (($order->promocode && $order->promocode->summ) || !$order->promocode),
            'bonusesPromo'         => $virtualBonusPromo,
            'bonusesPromoCheck'    => $order->paybonuses_promo && (($order->promocode && $order->promocode->summ) || !$order->promocode),
            'partnersBalance'      => $user->balance,
            'partnersBonusCheck'   => (bool)$order->paybalance,
            'partnersMaxPay'       => $order->maxPartner,
            'paybalance'           => $order->paybalance,
            'buttonDefaultPrice'   => $this->getDefaultButtonPrice($order),
            'sale'                 => $order->sale,
            'cashBackPay'          => (int)$this->repository->getOrderCashBackDiscount($order->user_id),
        ];
    }

    /**
     * Получаем данные промокода, если он применен к заказу
     * @param Order $order
     * @return array
     * @throws Exception
     */
    private function getOrderPromoCodeData(Order $order): array
    {
        if ($order->promocode) {
            $rurSale = 0;
            if ($order->promocode->settings->discount_check) { // включена ли скидка в промокоде
                if ($order->promocode->settings->discount_type) { // если скидка в рублях
                    /**
                     * Если промокод покрывает 40% стоимости (как по умолчанию в системе)
                     * Тогда возьмем минимальное из значений (40% от стоимости заказа или размер скидки промокода)
                     * Иначе возьмем стоимость заказа и вычислим размер скидки по % указанным в промокоде
                     * Результат округлим до большего целого
                     */
                    $rurSale = ceil(
                        $order->promocode->settings->discount_rur_percent == 40
                            ? min($order->price * 0.4, $order->promocode->settings->discount_rur)
                            : ($order->price / 100) * $order->promocode->settings->discount_rur_percent
                    );
                } else { // Если скидка в %, вычислим её размер и округлим до большего целого
                    $sale = $order->promocode->settings->discount;
                    $rurSale = ceil(($order->price / 100) * $order->promocode->settings->discount);
                }
            }
            return [
                'code'     => $order->promocode->code,
                'summ'     => $order->promocode->summ,
                'discount' => $rurSale ?? false,
                'info'     => $this->getPromoCodeInfo($order->promocode, $order, $rurSale),
                'sale'     => $sale ?? false,
            ];
        } else {
            return [];
        }
    }

    /**
     * Получить информацию по промокоду для блока пояснений
     * @param Promocode $promocode
     * @param Order $order
     * @param int $rurSale
     * @return string
     * @throws Exception
     */
    private function getPromoCodeInfo(Promocode $promocode, Order $order, int $rurSale): string
    {
        if (!$promocode->settings) {
            return '';
        }
        /**
         * Итоговая скидка по промокоду в ₽
         */
        $infoDiscount = '';
        if ($promocode->settings->discount_check && $rurSale != 0) {
            $infoDiscount = '<span class="promo-code-info-text text-black">Итоговая скидка по промокоду <b>' . $rurSale . ' ₽</b></span>';
        }

        /**
         * текст к промокоду (если есть)
         */
        $infoText = '';
        if ($promocode->settings->text_check && $promocode->settings->text) {
            if ($infoDiscount != '') {
                $infoText .= ' <b>+</b><br />';
            }
            $infoText .= '<span class="promo-code-info-text text-black">' . $promocode->settings->text . '</span>';
        }

        /**
         * действителен до (приоритет на "N дней с момента первого ввода", если не задано, то основной лимит)
         */
        $infoValidTo = '';
        if ($promocode->settings->n_days_check && $promocode->settings->n_days > 0) {
            $usageInfo = $this->repository->getPromoCodeUsageStatistic($promocode->id, $order->user_id);
            $datetime = new DateTime($usageInfo->created_at);
            $datetime->modify('+' . $usageInfo->usage_period . ' day');
            $newDate = $datetime->format('H:i d.m.Y');
            $infoValidTo = $promocode->settings->n_days_check
                ? '<span class="promo-code-info-text text-red">Действителен до ' . $newDate . '</span>' : '';
        } elseif ($promocode->settings->limit) {
            $datetime = new DateTime($promocode->settings->date_limit);
            $newDate = $datetime->format('H:i d.m.Y');
            $infoValidTo = $promocode->settings->limit
                ? '<span class="promo-code-info-text text-red">Действителен до ' . $newDate . '</span>' : '';
        }

        /**
         * Не суммируется с другими акциями
         */
        $infoPromoCodeSum = '';
        if ($promocode->settings->discount_check && $promocode->settings->discount_type) {
            if (!$promocode->summ) {
                $infoPromoCodeSum = '<span class="promo-code-info-text text-gray flex-block">Не суммируется с другими акциями<img src="/images/icons/warning.svg">';
            }
        }
        return $infoDiscount . $infoText . $infoValidTo . $infoPromoCodeSum;
    }

    /**
     * Получить возможные скидки к заказу
     * @param Order $order
     * @param $request
     * @param bool $fullPayment
     * @return array
     * @throws Exception|GuzzleException
     */
    public function getOrderDiscount(Order $order, $request, bool $fullPayment = false): array
    {
        $user = CrmUser::find($order->user_id);
        $this->getOrderBaseData($order, $request->userId, true);              // Общая информация по заказу
        $orderSalePrices = $this->getOrderPricesForSale($order, $fullPayment);
        $priceForCashBack = $orderSalePrices['priceForCashBack'];
        $priceForSale = $orderSalePrices['priceForSale'];  // Цена заказа с уже примененными % скидки и/или промокодом
        $order->payed = $this->getOrderPayed($order);                      // Сумма оплат по заказу, если они есть
        $this->getOrderPriceChanges($order);                               // Изменения стоимости заказа, если они есть
        $discountSum = 0;
        $buttonPrice = $fullPayment ? $priceForSale : $order->prepayPrice; // базовая цена для кнопки
        if ($order->payed) {                                            // Если уже были оплаты, то вычитаем из базовой цены
            if ($order->payed >= $order->prepayPrice || $fullPayment) { // Если оплаты больше чем размер предоплаты, или стоит галочка полной оплаты
                $buttonPrice = $priceForSale - $order->payed;    // Вычитаем из стоимости заказа к оплате
            } else {
                $buttonPrice = $order->prepayPrice - $order->payed;     // Иначе вычитаем из размера предоплаты
            }
        }
        /** Получим размер бонусов, а так же модифицируем цену для кнопки в зависимости от наличния промокода в заказе и того суммируется он или нет */
        $bonuses = $this->getBonuses($request->virtualBonusesFlag, $request->virtualBonusesPromoFlag, $request->cashBackBonusesFlag, $order, $order->user_id, $fullPayment);
        if ($order->promocode) { // Если промокод подключен к заказу
            if ($order->promocode->summ) { // Если промокод суммируется
                if ($bonuses['cashBackValue']) {
                    $buttonPrice -= $bonuses['cashBackValue']; // Модифицируем цену для кнопки вычитая из неё размер выбранных бонусов
                }
                if ($bonuses['bonusesSum']) {
                    $buttonPrice -= $bonuses['bonusesSum']; // Модифицируем цену для кнопки вычитая из неё размер выбранных бонусов
                }
            }
        } else { // Если промокод не подключен к заказу
            if ($bonuses['cashBackValue']) {
                $buttonPrice -= $bonuses['cashBackValue']; // Модифицируем цену для кнопки вычитая из неё размер выбранных бонусов
            }
            if ($bonuses['bonusesSum']) {
                $buttonPrice -= $bonuses['bonusesSum']; // Модифицируем цену для кнопки вычитая из неё размер выбранных бонусов
            }
        }
        $partnersBonusesMaxPay = $user->balance <= $buttonPrice ? $user->balance
            : $buttonPrice; // Получим максимально возможный размер партнерских бонусов для оплаты в заказе
        $bonus = $this->getPartnerBonuses($buttonPrice, $request->partnerBonusesValue, $request->partnerBonusesFlag, $partnersBonusesMaxPay, $discountSum, $order->id);
        $promocodeCashBack = ($this->promoCodeSettings && $this->promoCodeSettings->cashback_check)
            ? $this->promoCodeSettings->cashback : false;

        /** Если есть кешбек от промокода, используем его
         * Иначе проверяем размер % кешбэка пользователя и если он равен 0, то принудительно выставляем кешбэк равный 3% */
        $cashBackPercent = ($promocodeCashBack ? (int)$promocodeCashBack
            : ($order->cash_back_percent ? (int)$order->cash_back_percent : ($user->sale > 0 ? $user->sale : 3)));

        /** Блокировка применения кешбэка для старых заказов, ранее указанной в настройках даты */
        /** Блокировка применения кешбэка для клиентов с источником Wizard24 */
        if ($order->create > $this->cashBackReleaseDate || in_array($this->userSource, self::WIZARD_SOURCE)) {
            if ($order->payed < $order->priceForSale || $order->payed == 0) {
                $cashBack = ceil((($priceForCashBack - $bonuses['cashBackValue'] - $bonuses['bonusesSum'] - $discountSum - $this->getOrderPayed($order, 'bonus')) / 100) * $cashBackPercent);
            } else {
                $cashBackFromOrder = OrderCashBack::find($order->cash_back_id);
                $cashBack = $cashBackFromOrder->value;
            }
            $this->setCashBackForOrder($order, $user, $cashBack);
            $order->sale = false;
        } else {
            $cashBack = false;
        }

        /** Если на кнопке отрицательное значение, выведем 0 */
        if ($buttonPrice < 0) {
            $buttonPrice = 0;
        }

        /** Вычисляем сумму, которую в шаблоне передаем в модуль оплаты */
        if ($fullPayment) {
            $sumForTemplates = $priceForSale - $order->payed;
        } elseif ($order->prepayPrice > $order->payed) {
            $sumForTemplates = $order->prepayPrice - $order->payed;
        } else {
            $sumForTemplates = $priceForSale - $order->payed;
        }

        return [
            'status'          => true,
            'buttonPrice'     => $buttonPrice,
            'sum'             => $sumForTemplates,
            'fullPaymentFlag' => $request->fullPaymentFlag,
            'bonusFlag'       => $request->virtualBonusesFlag,
            'bonusValue'      => $bonuses['bonusValue'],
            'bonusPromoFlag'  => $request->virtualBonusesPromoFlag,
            'promoBonusValue' => $bonuses['promoBonusValue'],
            'cashBackFlag'    => $request->cashBackBonusesFlag,
            'cashBackPay'     => $bonuses['cashBackVisual'],
            'balancePayFlag'  => $request->partnerBonusesFlag,
            'cashBack'        => $cashBack,
            'paySumm'         => $bonus,
            'promoCode'       => $order->promocode ? 1 : 0,
        ];
    }

    /**
     * Получаем размер партнерских бонусов и выставляем его сразу в таблице заказа
     * @param int $debt
     * @param int $bonusValue
     * @param bool $bonusFlag
     * @param int $bonusMax
     * @param int $bonusesSum
     * @param int $orderId
     * @return int|null
     */
    private function getPartnerBonuses(int &$debt, int $bonusValue, bool $bonusFlag, int $bonusMax, int &$bonusesSum, int $orderId): ?int
    {
        if ($bonusFlag) {
            if ($bonusValue) {
                if ($bonusValue >= $debt) {
                    $bonusValue = $debt;
                    $debt = 0;
                } else {
                    $debt -= $bonusValue;
                }
            } else {
                if ($bonusMax <= $debt) {
                    $debt -= $bonusMax;
                    $bonusValue = $bonusMax;
                } else {
                    $bonusValue = $debt;
                    $debt = 0;
                }
            }
            $bonusesSum += $bonusValue;

            $this->repository->setOrderDiscounts($orderId, ['paybalance' => max($bonusValue, 0)]);
        } else {
            $this->repository->setOrderDiscounts($orderId, ['paybalance' => 0]);
            $bonusValue = 0;
        }

        return $bonusValue;
    }

    /**
     * Сумма оплат по заказу
     * @param Order $order
     * @param string $paySource
     * @return int|null
     */
    public function getOrderPayed(Order $order, string $paySource = 'all'): ?int
    {
        $where = [
            'order_id' => $order->id,
            'active'   => 1,
            'type'     => 2
        ];
        $query = OrderFinances::where($where);
        switch ($paySource) {
            case 'bonus':
                $result = $query->whereIn('pay_source', ['bonuses', 'balance', 'cash-back']);
                break;
            case 'real':
                $result = $query->whereNotIn('pay_source', ['bonuses', 'balance', 'cash-back']);
                break;
            case 'all':
                $result = $query;
                break;
        }

        return $result->sum('sum');
    }

    /**
     * Получаем размеры бонусов для заказа
     * @param bool $bonusFlag
     * @param bool $bonusPromoFlag
     * @param bool $cashBackBonusesFlag
     * @param Order $order
     * @param int $userId
     * @param bool $fullPayment
     * @return array|null
     */
    private function getBonuses(bool $bonusFlag, bool $bonusPromoFlag, bool $cashBackBonusesFlag, Order $order, int $userId, bool $fullPayment = false): ?array
    {
        $bonus = $promoBonus = $cashBackPay = 0;
        $orderPercent = ceil($order->priceForSale * 0.4);
        $bonusVisual = $this->getOrderVirtualDiscount($order, $userId);
        $promoBonusVisual = $this->getOrderVirtualDiscount($order, $userId, true);
        $cashBackVisual = (int)$this->repository->getOrderCashBackDiscount($userId);
        $this->setOrderCashBackPay($order->id, 0);
        if ($cashBackBonusesFlag) {
            $cashBackPay = min((int)($order->priceForSale - $order->payed), (int)$cashBackVisual);
            /** Если доплата больше нуля и небыло оплат по заказу, или же если не стоит галка полной оплаты */
            /** Тогда мы кешбэком оплачиваем указанную предоплату, иначе оплачиваем весь заказ кешбэком */
            if ($order->payed > 0 && !$fullPayment) {
                $cashBackPay = min((int)$order->surcharge, (int)$cashBackPay);
            }
            $this->setOrderCashBackPay($order->id, $cashBackPay);
        }
        if ($bonusFlag) {
            $bonus = $this->getOrderVirtualDiscount($order, $userId);
            $diff = $orderPercent - $bonus;
            $promoBonusVisual = min($promoBonusVisual, $diff);
            $this->repository->setOrderDiscounts($order->id, ['paybonuses' => max($bonus, 0)]);
        } else {
            $this->repository->setOrderDiscounts($order->id, ['paybonuses' => 0]);
        }
        if ($bonusPromoFlag && $bonus < $orderPercent) {
            $diff = $orderPercent - $bonus;
            $bonusVisual = $bonus;
            $promoBonus = min($this->getOrderVirtualDiscount($order, $userId, true), $diff);
            $this->repository->setOrderDiscounts($order->id, ['paybonuses_promo' => max($promoBonus, 0)]);
        } else {
            $this->repository->setOrderDiscounts($order->id, ['paybonuses_promo' => 0]);
        }
        $bonusesSumm = min($order->priceForSale - $cashBackPay, $bonus + $promoBonus);
        return [
            'bonusesSum'      => $bonusesSumm,
            'bonusValue'      => $bonusVisual,
            'promoBonusValue' => $promoBonusVisual,
            'cashBackValue'   => $cashBackPay,
            'cashBackVisual'  => $cashBackVisual,
        ];

    }

    /**
     * Получаем размер виртуальных бонусов/промокод-бонусов
     * @param Order $order
     * @param int $userId
     * @param $promo
     * @return int|null
     */
    private function getOrderVirtualDiscount(Order $order, int $userId, $promo = false): ?int
    {
        $bonuses = $this->repository->getUserBonusesSumm($order->id, $userId, $promo ? 3 : 1);
        if ($order->prepay >= 40) {
            $percentOfOrder = ceil($order->priceForSale * 0.4); // Вычисляем 40% от стоимости заказа
        } else {
            // если предоплата меньше 40%, ограничим списание бонусов в предоплате до размера предоплаты
            $percentOfOrder = ceil(($order->priceForSale / 100) * $order->prepay);
        }
        return min((int)$bonuses, $percentOfOrder);
    }

    /**
     * Получаем читаемый статус заказа
     * @param Order $order
     * @return string
     */
    private function getOrderStatus(Order $order): string
    {
        if (in_array($order->status, ['search_author', 'auto_estimated_paid'])) {
            $status = 'В работе';
        } elseif (in_array($order->status, ['request', 'auto_estimated'])) {
            if ($order->priceForSale) {
                $status = 'Оценён';
            } else {
                $status = 'Ожидает информации';
            }
        } else {
            $res = OrderStatus::where('cod', '=', $order->status)->first();
            $status = $res->name;
        }

        return $status;
    }

    /**
     * Получаем читаемый предмет заказа
     * @param int $courseId
     * @return string
     */
    private function getOrderCourse(int $courseId): string
    {
        $course = OrderCourse::find($courseId);
        return $course ? $course->name : '';
    }

    /**
     * Получаем читаемый тип работы
     * @param int $typeOfWorkId
     * @return string
     */
    private function getOrderTypeOfWork(int $typeOfWorkId): string
    {
        $typeOfWork = OrderTypeOfWork::find($typeOfWorkId);
        return $typeOfWork ? $typeOfWork->name : '';
    }

    /**
     * Получаем читаемый размер шрифта
     * @param int $fontSizeId
     * @return string|null
     */
    private function getOrderFontSize(int $fontSizeId): ?string
    {
        $fontSize = OrderFontSize::find($fontSizeId);
        return $fontSize ? $fontSize->name : NULL;
    }

    /**
     * Получаем читаемый межстрочный интервал
     * @param int $fontIntervalId
     * @return string
     */
    private function getOrderFontInterval(int $fontIntervalId): ?string
    {
        $fontInterval = OrderFontInterval::find($fontIntervalId);
        return $fontInterval ? $fontInterval->name : NULL;
    }

    /**
     * Получаем читаемый тип проверки оригинальности работы
     * @param int $originalityCheckId
     * @return string|null
     */
    private function getOrderOriginalityCheck(int $originalityCheckId): ?string
    {
        $originalityCheck = OrderOriginalityCheck::find($originalityCheckId);
        return $originalityCheck ? $originalityCheck->name : NULL;
    }

    /**
     * Получаем цену заказа с уже примененными % скидки и/или промокодом
     * $percentForSale - общий процент скидки для заказа
     * с учетом скидок от менеджера и введенным промокодом
     * @param Order $order
     * @param bool $fullPayment
     * @return array|null
     */
    public function getOrderPricesForSale(Order $order, bool $fullPayment = false): ?array
    {
        $percentBonuses = ($this->repository->getPercentBonuses($order->id, $order->user_id))->first();
        $bonusSale = $percentBonuses // Получаем сумму бонусных скидок в % если они есть
            ? $percentBonuses->sum
            : 0;
        $bonusSaleOutDate = $percentBonuses // Получаем дату окончания ближайшей процентной бонусной скидки
            ? $percentBonuses->available
            : false;
        $percentForSale = $bonusSale ?? 0;
        if ($order->create <= $this->cashBackReleaseDate) {
            $percentForSale += floor($order->sale);
        }
        if ($order->source == 'myknow') {
            $percentForSale = $order->sale;
        }
        if ($order->promocode) {
            if (!$order->promocode->summ) {
                $percentForSale = 0;
            }
            $promoCodePrice = false;
            $percentPromoCode = 0;
            if ($order->promocode->settings->discount_check && !$order->promocode->settings->discount_type) {
                $percentPromoCode = $order->promocode->settings->discount;
            }
            $result = $this->getResultOrderChange($order, $percentPromoCode);
            if ($order->promocode->settings->discount_check && $order->promocode->settings->discount_type) {
                $promoCodePrice = ceil($order->promocode->settings->discount_rur_percent == 40
                    ? min($result * 0.4, $order->promocode->settings->discount_rur)
                    : ($result / 100) * $order->promocode->settings->discount_rur_percent
                );
            }
            $result -= $promoCodePrice;
            $result = $this->getResultOrderChange($order, $percentForSale, $result);
        } else {
            $result = $this->getResultOrderChange($order, $percentForSale);
        }
        $priceForCashBack = $order->price - ceil($order->price / 100 * $percentForSale);

        return [
            'priceForSale'     => $result, 'bonusSale' => $bonusSale, 'bonusOutDate' => $bonusSaleOutDate,
            'priceForCashBack' => $priceForCashBack
        ];
    }

    /**
     * Получаем базовое значение для цены, указанной на кнопке
     * до любых модификаций. Сделано для стартового вывода цены на кнопку
     * @param Order $order
     * @return int
     */
    private function getDefaultButtonPrice(Order $order): int
    {
        $bonuses = 0;
        if ($order->payed) {
            $result = $order->payed >= $order->prepayPrice
                ? ($order->priceForSale
                    ? $order->priceForSale - $order->payed
                    : $order->price - $order->payed)
                : $order->prepayPrice - $order->payed;
        } else {
            $result = $order->prepayPrice;
        }
        if ($order->promocode) {
            if ($order->promocode->summ) {
                if ($order->paybonuses) {
                    $bonuses += $order->paybonuses;
                }
                if ($order->paybonuses_promo) {
                    $bonuses += $order->paybonuses_promo;
                }
            }
        } else {
            if ($order->paybonuses) {
                $bonuses += $order->paybonuses;
            }
            if ($order->paybonuses_promo) {
                $bonuses += $order->paybonuses_promo;
            }
        }
        if ($bonuses) {
            $result -= min((int)$order->priceForSale * 0.4, (int)$bonuses);
        }
        if ($order->paybalance) {
            $result -= $order->paybalance;
        }

        if ($result < 0) {
            $result = 0;
        }
        return $result;
    }

    /**
     * Модификация данных заказа для фронта
     * @param Order $order
     * @param int $userId
     * @param array $orderBaseData
     * @return void
     */
    public function getOrderInfo(Order $order, int $userId, array &$orderBaseData): void
    {
        $result = [
            'parseForFront' => ['id' => $order->id, 'status' => $order->status],
        ];
        $result = array_merge($result, $this->getOrderFiles($order, $userId));
        $orderBaseData = array_merge($orderBaseData, $result);
    }

    /**
     * Получить файлы заказа
     * @param Order $order
     * @param int $userId
     * @return array
     */
    private function getOrderFiles(Order $order, int $userId): array
    {

        $orderFiles = Files::where('order_id', $order->id)->active()->readyForClient()->get()->groupBy('file_type')
            ->toArray();

        $notUploadedFiles = Files::where('order_id', 0)->active()->readyForClient()->fromUser($userId)->get()
            ->toArray();

        return ['files' => $orderFiles, 'nfiles' => $notUploadedFiles ?: false];
    }

    /**
     * Получение изменеий цены заказа
     * и размера изменений
     * @param Order $order
     * @return void
     */
    private function getOrderPriceChanges(Order $order): void
    {
        $incrementPrice = $this->repository->getOrderPriceIncrement($order);
        $decrementPrice = $this->repository->getOrderPriceDecrement($order);
        $order->change = $incrementPrice - $decrementPrice;
        $order->priceChange = $order->price + $order->change;

    }

    /**
     * Перенесено из старого кода
     * до конца так и не понял для чего это, но без него не работает :(
     * вытягивает данные по договору на заказ
     * @param Order $order
     * @return array|array[]
     */
    private function getOrderContract(Order $order): array
    {
        $office = $this->repository->getBuilder()
            ->table('offices')
            ->first();
        $office = $order->office_id
            ? $office
            : [];

        $contracts = [];
        $legal = $this->repository->getBuilder()
            ->table('directory_legal')
            ->select('id')
            ->get()
            ->toArray();
        $legals = array_column($legal, NULL, 'id');

        if ($order->contract_id) {
            $contracts = [
                'online'  => $legals[$order->contract_id] ?? [],
                'offline' => $legals[$order->contract_id] ?? [],
            ];
        } else {
            $contracts = [
                'online'  => [],
                'offline' => $legals[$office->contract_id ?? 0] ?? [],
            ];
            $moneyActiveLegal = $this->repository->getBuilder()
                ->table('settings_vars')
                ->select('value')
                ->where('name', 'MoneyActiveLegal')
                ->first()->value;
            if ($order->office_id == 2 && $moneyActiveLegal) {
                $contracts['online'] = $legals[$moneyActiveLegal] ?? [];
            } else {
                $contracts['online'] = $legals[$office->contract_id ?? 0] ?? [];
            }
        }
        return $contracts;
    }

    /**
     * Проверка на количество дней с первого ввода промокода
     * @param Order $order
     * @return void
     * @throws Exception
     */
    private function checkPromocodeDaysLeft(Order $order): void
    {
        if (!$order->promocode) {
            return;
        }
        $dateNow = new DateTime();
        $codeUsageInfo = $this->repository->getPromoCodeUsageStatistic($order->promocode_id, $order->user_id);
        if ($codeUsageInfo) {
            $codeUsageDate = new DateTime($codeUsageInfo->created_at);
            $compireDate = date_timestamp_get($codeUsageDate) - date_timestamp_get($dateNow);
            if ($compireDate > ($order->promocode->settings->n_days * 86400)) {
                $updatedOrder = Order::find($order->id);
                $updatedOrder->promocode_id = 0;
                $updatedOrder->save();
            }
        } elseif ($order->promocode->settings->limit && $order->promocode->settings->date_limit) {
            $codeUsageDate = new DateTime($order->promocode->settings->date_limit);
            $compireDate = date_timestamp_get($codeUsageDate) - date_timestamp_get($dateNow);
            if ($compireDate <= 0) {
                $updatedOrder = Order::find($order->id);
                $updatedOrder->promocode_id = 0;
                $updatedOrder->save();
            }
        }
    }

    /**
     * Проверка на "первый заказ"
     * @param Order $order
     * @return void
     */
    private function checkPromocodeFirstOrder(Order $order): void
    {
        if (!$order->promocode) {
            return;
        }
        $orders = $ordersFin = [];
        $ordersList = (Order::where(['user_id' => $order->user_id])->get('id')->toArray());
        foreach ($ordersList as $item) {
            $orders[] = $item['id'];
        }
        $ordersFinances = (OrderFinances::whereIn('order_id', $orders)->get('order_id'))->toArray();
        foreach ($ordersFinances as $item) {
            $ordersFin[] = $item['order_id'];
        }
        if ($order->promocode->first_order && count($ordersFin) > 0) {
            $updatedOrderCollection = Order::where(['user_id' => $order->user_id])->whereNotIn('id', $ordersFin)->get();
            $updatedOrderCollection->each(function ($item, $key) {
                $item->promocode_id = 0;
                $item->save();
            });
        }
    }

    /**
     * Получаем стоимость заказа в завиимости от того, были модификации стоимости или нет
     * @param Order $order
     * @param mixed $percentForSale
     * @param int $modifyedPrice
     * @return float|int|mixed
     */
    private function getResultOrderChange(Order $order, mixed $percentForSale, int $modifyedPrice = 0): mixed
    {
        if ($modifyedPrice) {
            $result = $modifyedPrice - ceil($modifyedPrice / 100 * $percentForSale);
        } else {
            if ($order->change) {
                $result = $order->change - ceil($order->change / 100 * floor($percentForSale));
                $result = $order->price - ceil($order->price / 100 * $percentForSale) + $result;
            } else {
                $result = $order->price - ceil($order->price / 100 * $percentForSale);
            }
        }

        return $result;
    }

    /**
     * Установить размер кешбэка для заказа
     * @param Order $order
     * @param CrmUser $user
     * @param int $cashBack
     * @return void
     */
    public function setCashBackForOrder(Order $order, CrmUser $user, int $cashBack): void
    {
        $cashBackTable = OrderCashBack::where(['order_id' => $order->id])->first();
        if (!$cashBackTable) {
            $cashBackTable = new OrderCashBack();
            $cashBackTable->order_id = $order->id;
            $cashBackTable->user_id = $user->id;
        }
        if (($order->create > $this->cashBackReleaseDate && ($order->payed < $order->priceForSale || $order->payed == 0)) || in_array($user->source, self::WIZARD_SOURCE)) {
            $cashBackTable->value = $cashBack;
            $cashBackTable->push();

            $order = Order::find($order->id);
            $order->cash_back_id = $cashBackTable ? $cashBackTable['id'] : 0;
            $order->save();
        } elseif ($order->total == 0) {
            $cashBackTable->value = 0;
            $cashBackTable->push();
        }
    }

    /**
     * @param int $orderId
     * @param int $cashBackPay
     * @return void
     */
    private function setOrderCashBackPay(int $orderId, int $cashBackPay): void
    {
        $this->repository->getBuilder()
            ->table('orders')
            ->where(['id' => $orderId])
            ->update(['cash_back_payment' => $cashBackPay]);
    }

    /**
     * @param Order $order
     * @param int|null $cashBackPercent
     * @return float
     */
    public function checkOrderCashBackSize(Order $order, ?int $cashBackPercent): float
    {
        $user = CrmUser::find($order->user_id);
        $this->getOrderPriceChanges($order);
        $orderPricesForSale = $this->getOrderPricesForSale($order);
        $priceForCashBack = $orderPricesForSale['priceForCashBack'];
        $cashBackPercent = $cashBackPercent ?? $order->cash_back_percent;
        $bonusesPayed = (int)OrderFinances::where(['order_id' => $order->id])->whereIn('pay_source', [
            'bonuses', 'cash-back', 'balance'
        ])->sum('sum');
        $cashBack = ceil((($priceForCashBack - $bonusesPayed) / 100) * $cashBackPercent);
        $this->setCashBackForOrder($order, $user, $cashBack);

        return $cashBack;
    }

}
