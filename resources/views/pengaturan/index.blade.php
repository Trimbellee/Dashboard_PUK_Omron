@extends('layouts.app')

@section('title', 'Pengaturan | PUK OMRON')

@section('content')
<style>
    .page-header { margin-bottom: 24px; }
    .page-header h1 {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 4px;
        letter-spacing: -0.01em;
    }
    .page-header p {
        font-size: 13.5px;
        color: var(--text-muted);
        margin: 0;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
    }

    .settings-card {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 20px;
        background: var(--bg-surface);
        border: 1px solid var(--border);
        border-radius: 14px;
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .settings-card::after {
        content: '→';
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 16px;
        color: var(--text-muted);
        transition: transform 0.2s, color 0.2s;
    }

    .settings-card:hover {
        border-color: var(--border-strong);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .settings-card:hover::after {
        transform: translateX(3px);
        color: var(--accent);
    }

    .settings-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        background: var(--accent-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .settings-card-body { flex: 1; min-width: 0; }

    .settings-card-title {
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 4px;
        line-height: 1.3;
    }

    .settings-card-desc {
        font-size: 12.5px;
        color: var(--text-muted);
        margin: 0;
        line-height: 1.45;
    }
</style>

<div class="page-header">
    <h1>Pengaturan</h1>
    <p>Kelola konfigurasi sistem PUK OMRON</p>
</div>

<div class="settings-grid">
    @foreach($menus as $menu)
        <a href="{{ route($menu['route']) }}" class="settings-card">
            <div class="settings-card-icon">{{ $menu['icon'] }}</div>
            <div class="settings-card-body">
                <h3 class="settings-card-title">{{ $menu['title'] }}</h3>
                <p class="settings-card-desc">{{ $menu['description'] }}</p>
            </div>
        </a>
    @endforeach
</div>
@endsection