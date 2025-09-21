@props([
    'title' => 'Metric',
    'value' => '0',
    'icon' => 'fas fa-chart-line',
    'change' => null,
    'changeType' => 'neutral', // positive, negative, neutral
    'class' => ''
])

<div class="metric-card {{ $class }}">
    <div class="metric-icon">
        <i class="{{ $icon }}"></i>
    </div>
    
    <div class="metric-value">{{ $value }}</div>
    
    <div class="metric-label">{{ $title }}</div>
    
    @if($change !== null)
        <div class="metric-change {{ $changeType }}">
            @if($changeType === 'positive')
                <i class="fas fa-arrow-up"></i>
            @elseif($changeType === 'negative')
                <i class="fas fa-arrow-down"></i>
            @else
                <i class="fas fa-minus"></i>
            @endif
            <span>{{ $change }}</span>
        </div>
    @endif
</div>