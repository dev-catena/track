@php $userName = $user->name ?? ''; @endphp
<div class="d-flex align-items-center gap-2">
    @if(!empty($user->avatar))
        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $userName }}" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
    @else
        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 36px; height: 36px; font-size: 0.9rem;">
            {{ $userName ? strtoupper(mb_substr($userName, 0, 1)) : '?' }}
        </div>
    @endif
    <span class="text-nowrap" title="{{ $userName }}">{{ $userName ?: 'Sem nome' }}</span>
</div>
