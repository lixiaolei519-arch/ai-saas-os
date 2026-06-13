<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {
    }

    public function users(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->users($this->limit($request)),
        ]);
    }

    public function tenants(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->tenants($this->limit($request)),
        ]);
    }

    public function licenses(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->licenses($this->limit($request)),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->orders($this->limit($request)),
        ]);
    }

    public function paymentCallbacks(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->paymentCallbacks($this->limit($request)),
        ]);
    }

    public function channels(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->channels($this->limit($request)),
        ]);
    }

    public function commissions(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->commissions($this->limit($request)),
        ]);
    }

    public function aiUsageRecords(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiUsageRecords($this->limit($request)),
        ]);
    }

    public function plugins(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->plugins($this->limit($request)),
        ]);
    }

    public function pluginDownloadRecords(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->pluginDownloadRecords($this->limit($request)),
        ]);
    }

    public function workflows(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->workflowDefinitions($this->limit($request)),
        ]);
    }

    public function workflowRuns(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->workflowRuns($this->limit($request)),
        ]);
    }

    public function workflowEvents(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->workflowEventLogs($this->limit($request)),
        ]);
    }

    public function aiCompanyDashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyDashboard(),
        ]);
    }

    public function aiCompanyTasks(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyTasks($this->limit($request)),
        ]);
    }

    public function aiCompanyIdeas(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyIdeas($this->limit($request)),
        ]);
    }

    public function aiCompanyRoadmaps(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyRoadmaps($this->limit($request)),
        ]);
    }

    public function aiCompanyReleasePlans(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyReleasePlans($this->limit($request)),
        ]);
    }

    public function aiCompanyQualityReports(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyQualityReports($this->limit($request)),
        ]);
    }

    public function aiCompanyRiskReports(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyRiskReports($this->limit($request)),
        ]);
    }

    public function aiCompanyCodexPrompts(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyCodexPrompts($this->limit($request)),
        ]);
    }

    public function aiCompanyDailyReports(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->aiCompanyDailyReports($this->limit($request)),
        ]);
    }

    public function selfEvolutionDashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->selfEvolutionDashboard(),
        ]);
    }

    public function selfEvolutionScans(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->selfEvolutionScans($this->limit($request)),
        ]);
    }

    public function selfEvolutionScores(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->selfEvolutionScores($this->limit($request)),
        ]);
    }

    public function selfEvolutionPlans(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->selfEvolutionPlans($this->limit($request)),
        ]);
    }

    public function selfEvolutionReleaseReviews(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->selfEvolutionReleaseReviews($this->limit($request)),
        ]);
    }

    public function selfEvolutionSuggestions(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->selfEvolutionSuggestions($this->limit($request)),
        ]);
    }

    public function autonomousOperationsDashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationsDashboard(),
        ]);
    }

    public function autonomousOperationReports(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationReports($this->limit($request)),
        ]);
    }

    public function autonomousOperationSeoPlans(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationSeoPlans($this->limit($request)),
        ]);
    }

    public function autonomousOperationLandingPages(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationLandingPages($this->limit($request)),
        ]);
    }

    public function autonomousOperationPricing(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationPricing($this->limit($request)),
        ]);
    }

    public function autonomousOperationReleaseAnnouncements(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationReleaseAnnouncements($this->limit($request)),
        ]);
    }

    public function autonomousOperationCustomerEmails(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationCustomerEmails($this->limit($request)),
        ]);
    }

    public function autonomousOperationFaq(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationFaq($this->limit($request)),
        ]);
    }

    public function autonomousOperationPartnerRecruiting(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->autonomousOperationPartnerRecruiting($this->limit($request)),
        ]);
    }

    public function productFactoryDashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->productFactoryDashboard(),
        ]);
    }

    public function productFactoryProductTemplates(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->productFactoryProductTemplates($this->limit($request)),
        ]);
    }

    public function productFactoryPluginTemplates(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->productFactoryPluginTemplates($this->limit($request)),
        ]);
    }

    public function productFactoryLandingPageTemplates(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->productFactoryLandingPageTemplates($this->limit($request)),
        ]);
    }

    public function productFactoryPackageTemplates(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->productFactoryPackageTemplates($this->limit($request)),
        ]);
    }

    public function productFactoryLaunchChecklists(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->productFactoryLaunchChecklists($this->limit($request)),
        ]);
    }

    public function qualityVersion(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->qualityVersion(),
        ]);
    }

    public function qualityDeployment(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->qualityDeployment(),
        ]);
    }

    public function qualityDocs(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->qualityDocs(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->stats(),
        ]);
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->dashboard(),
        ]);
    }

    public function system(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminService->system(),
        ]);
    }

    private function limit(Request $request): int
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return (int) ($data['limit'] ?? 50);
    }
}
