<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use App\Modules\Payments\Actions\RecordCashPaymentAction;
use App\Modules\Payments\Http\Requests\RecordCashPaymentRequest;
use Illuminate\Http\RedirectResponse;

class RecordCashPaymentController
{
    public function __invoke(
        Branch $branch,
        int $order,
        RecordCashPaymentRequest $request,
        RecordCashPaymentAction $action,
    ): RedirectResponse {
        $action->execute(
            $branch->id,
            $order,
            $request->user(),
            $request->validated('note'),
        );

        return back()->with('success', 'تم تسجيل استلام المبلغ النقدي بنجاح.');
    }
}
