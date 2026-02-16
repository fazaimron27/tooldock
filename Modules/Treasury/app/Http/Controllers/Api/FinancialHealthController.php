<?php

/**
 * Financial Health API Controller
 *
 * Provides REST API endpoints for financial health recommendations and data
 * status. Returns goal recommendations based on transaction history and
 * checks whether users have sufficient data for meaningful analysis.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Treasury\Services\FinancialHealthService;

/**
 * Class FinancialHealthController
 *
 * Provides API endpoints for financial health recommendations and data status.
 */
class FinancialHealthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  FinancialHealthService  $financialHealthService  The financial health service
     */
    public function __construct(
        private readonly FinancialHealthService $financialHealthService
    ) {}

    /**
     * Get goal recommendation based on category.
     *
     * Returns financial guidance and suggested target amounts for
     * Emergency & Security goal categories based on user's transaction history.
     *
     * @param  Request  $request  The incoming request
     * @return JsonResponse
     */
    public function getGoalRecommendation(Request $request): JsonResponse
    {
        Gate::authorize('treasuries.treasury.view');

        $categorySlug = $request->query('category_slug');
        $walletCurrency = $request->query('wallet_currency');

        if (empty($categorySlug)) {
            return response()->json([
                'has_data' => false,
                'data' => null,
                'message' => 'Category slug is required.',
            ], 400);
        }

        $user = $request->user();
        $recommendation = $this->financialHealthService->getGoalRecommendation(
            $user,
            $categorySlug,
            $walletCurrency
        );

        return response()->json([
            'has_data' => ! is_null($recommendation),
            'data' => $recommendation,
        ]);
    }

    /**
     * Get user's financial data status.
     *
     * Returns whether the user has enough transaction data for recommendations.
     *
     * @param  Request  $request  The incoming request
     * @return JsonResponse
     */
    public function getDataStatus(Request $request): JsonResponse
    {
        Gate::authorize('treasuries.treasury.view');

        $user = $request->user();
        $status = $this->financialHealthService->hasEnoughData($user);

        return response()->json([
            'has_expense_data' => $status['expenses'],
            'has_income_data' => $status['income'],
            'expense_months' => $status['expense_months'],
            'income_months' => $status['income_months'],
        ]);
    }
}
