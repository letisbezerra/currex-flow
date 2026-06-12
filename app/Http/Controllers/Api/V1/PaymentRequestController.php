<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\ApprovePaymentRequestAction;
use App\Actions\Payment\CreatePaymentRequestAction;
use App\Actions\Payment\RejectPaymentRequestAction;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentStatusRequest;
use App\Http\Resources\Api\V1\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentRequestController extends Controller
{
    public function __construct(
        private readonly CreatePaymentRequestAction $createAction,
        private readonly ApprovePaymentRequestAction $approveAction,
        private readonly RejectPaymentRequestAction $rejectAction,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = $user->isFinance()
            ? PaymentRequest::query()->with('user')
            : PaymentRequest::query()->where('user_id', $user->id);

        $payments = $query
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15);

        return PaymentRequestResource::collection($payments);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payment = ($this->createAction)($request->validated(), $user);

        return (new PaymentRequestResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PaymentRequest $payment): PaymentRequestResource
    {
        $this->authorize('view', $payment);

        return new PaymentRequestResource($payment->load('user'));
    }

    public function updateStatus(UpdatePaymentStatusRequest $request, PaymentRequest $payment): PaymentRequestResource
    {
        $this->authorize('updateStatus', $payment);

        /** @var User $user */
        $user = $request->user();

        $action = $request->input('status') === PaymentStatus::Approved->value
            ? $this->approveAction
            : $this->rejectAction;

        return new PaymentRequestResource(($action)($payment, $user));
    }
}
