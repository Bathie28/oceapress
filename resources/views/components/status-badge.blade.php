@props(['status', 'label' => null])
@php
    $statusStyle = match (strtoupper((string) $status)) {
        'EN_ATTENTE', 'EN_COURS' => 'background:#FEF3C7;color:#92400E',
        'EN_TRAITEMENT', 'CONFIRME', 'PAYEE', 'PAYÉE' => 'background:#DBEAFE;color:#1D4ED8',
        'PRETE', 'RECUPEREE', 'REUSSIE' => 'background:#DCFCE7;color:#166534',
        'ANNULEE', 'ECHOUE', 'ECHOUÉE', 'IMPAYEE' => 'background:#FEE2E2;color:#B91C1C',
        default => 'background:#DBEAFE;color:#2563EB',
    };
@endphp
<span class="op-badge" style="{{ $statusStyle }}">{{ $label ?? str_replace('_', ' ', $status) }}</span>
