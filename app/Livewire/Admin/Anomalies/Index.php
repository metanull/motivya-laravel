<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Anomalies;

use App\Enums\PaymentAnomalyType;
use App\Models\PaymentAnomaly;
use App\Models\User;
use App\Services\AnomalyDetectorService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $filterType = '';

    public ?int $resolvingAnomalyId = null;

    public string $resolveReason = '';

    public ?int $ignoringAnomalyId = null;

    public string $ignoreReason = '';

    public function mount(): void
    {
        Gate::authorize('access-admin-panel');
    }

    /**
     * @return LengthAwarePaginator<PaymentAnomaly>
     */
    #[Computed]
    public function anomalies(): LengthAwarePaginator
    {
        return PaymentAnomaly::with(['coach'])
            ->where('resolution_status', 'open')
            ->when($this->filterType !== '', fn ($q) => $q->where('anomaly_type', PaymentAnomalyType::from($this->filterType)))
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return array_map(
            fn (PaymentAnomalyType $t) => ['value' => $t->value, 'label' => $t->label()],
            PaymentAnomalyType::cases(),
        );
    }

    public function openResolveModal(int $anomalyId): void
    {
        $this->resolvingAnomalyId = $anomalyId;
        $this->resolveReason = '';
    }

    public function resolve(int $anomalyId, AnomalyDetectorService $service): void
    {
        Gate::authorize('access-admin-panel');

        $this->validate(['resolveReason' => 'required|string|min:3']);

        /** @var User $actor */
        $actor = auth()->user();
        $anomaly = PaymentAnomaly::findOrFail($anomalyId);

        $service->resolve($anomaly, $actor, $this->resolveReason);
        $this->dispatch('notify', type: 'success', message: __('admin.anomalies_resolved_success'));

        $this->resolvingAnomalyId = null;
        $this->resolveReason = '';
        unset($this->anomalies);
    }

    public function openIgnoreModal(int $anomalyId): void
    {
        $this->ignoringAnomalyId = $anomalyId;
        $this->ignoreReason = '';
    }

    public function ignore(int $anomalyId, AnomalyDetectorService $service): void
    {
        Gate::authorize('access-admin-panel');

        $this->validate(['ignoreReason' => 'required|string|min:3']);

        /** @var User $actor */
        $actor = auth()->user();
        $anomaly = PaymentAnomaly::findOrFail($anomalyId);

        $service->ignore($anomaly, $actor, $this->ignoreReason);
        $this->dispatch('notify', type: 'success', message: __('admin.anomalies_ignored_success'));

        $this->ignoringAnomalyId = null;
        $this->ignoreReason = '';
        unset($this->anomalies);
    }

    public function render(): View
    {
        return view('livewire.admin.anomalies.index')
            ->title(__('admin.anomalies_title'));
    }
}
