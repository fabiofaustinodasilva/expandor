@extends('layouts.operational')

@section('title', 'Resultados')

@section('page')
@php
    $teamView = $metrics->team_view;
    $funnel = $metrics->funnel;
    $productivity = $metrics->productivity;
@endphp
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Resultados</h1>
            <div class="header-meta">
                {{ $company->name }} ·
                {{ $teamView ? 'painel comercial do gestor' : 'seus resultados comerciais' }}
                · {{ $plan_name }}
            </div>
        </div>
        <div class="actions" style="gap:0.5rem;">
            <a class="btn {{ ($period ?? '') === 'today' ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('dashboard', array_filter(['period' => 'today', 'user_id' => $isSeller ? null : $filters->user_id])) }}">Hoje</a>
            <a class="btn {{ ($period ?? '') === '7d' ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('dashboard', array_filter(['period' => '7d', 'user_id' => $isSeller ? null : $filters->user_id])) }}">Últimos 7 dias</a>
            <a class="btn {{ ($period ?? '') === '30d' ? 'btn-primary' : 'btn-ghost' }}" href="{{ route('dashboard', array_filter(['period' => '30d', 'user_id' => $isSeller ? null : $filters->user_id])) }}">Últimos 30 dias</a>
            <a class="btn btn-ghost" href="{{ route('map.index') }}">Abrir mapa</a>
            @if(!empty($canViewCommissions))
                <a class="btn btn-ghost" href="{{ $commissionsUrl }}"
                   title="{{ $isSeller ? 'Minha comissão' : 'Gestão de comissões' }}">
                    {{ $isSeller ? '💰 Minha comissão' : '💰 Comissões' }}
                </a>
            @endif
        </div>
    </div>

    @if(isset($onboarding) && $onboarding && ! $onboarding->isCompleted)
        <div class="card" style="margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:center;">
                <div>
                    <strong>Setup</strong>
                    <div class="header-meta">{{ $onboarding->percent }}% concluído</div>
                </div>
                @if(auth()->user()?->hasPermission('onboarding.manage'))
                    <a class="btn btn-primary" href="{{ route('setup.show') }}">Continuar setup</a>
                @endif
            </div>
            <div style="margin-top:0.75rem; background:var(--bg-soft); border-radius:999px; overflow:hidden; height:12px;">
                <div style="width:{{ $onboarding->percent }}%; height:100%; background:var(--accent);"></div>
            </div>
            @if($onboarding->alerts)
                <ul style="margin:0.85rem 0 0; padding-left:1.1rem; color:var(--warning);">
                    @foreach($onboarding->alerts as $alert)
                        <li>{{ $alert }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @include('onboarding.partials.activation-card')
    @include('onboarding.partials.activation-guidance')

    <div class="card" style="margin-bottom:1rem;">
        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-4" style="align-items:end;">
            <input type="hidden" name="period" value="{{ $period }}">
            <div class="form-group" style="margin:0;">
                <label for="date_from">De</label>
                <input class="form-control" type="date" id="date_from" name="date_from" value="{{ $filters->date_from }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="date_to">Até</label>
                <input class="form-control" type="date" id="date_to" name="date_to" value="{{ $filters->date_to }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="city_id">Cidade</label>
                <select class="form-control" id="city_id" name="city_id">
                    <option value="">Todas</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected((string) $filters->city_id === (string) $city->id)>
                            {{ $city->name }}/{{ $city->state }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label for="sector_id">Setor</label>
                <select class="form-control" id="sector_id" name="sector_id">
                    <option value="">Todos</option>
                    @foreach($sectors as $sector)
                        <option value="{{ $sector->id }}" data-city-id="{{ $sector->city_id }}"
                            @selected((string) $filters->sector_id === (string) $sector->id)>
                            {{ $sector->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($teamView)
                <div class="form-group" style="margin:0;">
                    <label for="user_id">Vendedor</label>
                    <select class="form-control" id="user_id" name="user_id">
                        <option value="">Todos</option>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller['id'] }}" @selected((string) $filters->user_id === (string) $seller['id'])>
                                {{ $seller['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="actions" style="grid-column: 1 / -1;">
                <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                <a class="btn btn-ghost" href="{{ route('dashboard') }}">Limpar</a>
            </div>
        </form>
    </div>

    @if(count($metrics->alerts) > 0)
        <div class="card" style="margin-bottom:1rem; border-color:rgba(245,158,11,.4);">
            <h2 style="margin:0 0 .75rem; font-size:1.05rem; color:#fbbf24;">Atenção</h2>
            <ul style="margin:0; padding-left:1.1rem; display:grid; gap:.45rem;">
                @foreach($metrics->alerts as $alert)
                    <li>
                        <strong>{{ $alert['title'] }}</strong>
                        <span class="header-meta"> — {{ $alert['detail'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0; font-size:1.05rem;">Funil comercial</h2>
        <div class="funnel-row">
            <div class="funnel-step">
                <div class="stat-label">Pontos cadastrados</div>
                <div class="stat-value" style="font-size:1.6rem;">{{ $funnel['points'] }}</div>
            </div>
            <div class="funnel-arrow">↓ <span>{{ number_format($funnel['pct_visits'], 1, ',', '.') }}%</span></div>
            <div class="funnel-step">
                <div class="stat-label">Visitas</div>
                <div class="stat-value" style="font-size:1.6rem;">{{ $funnel['visits'] }}</div>
            </div>
            <div class="funnel-arrow">↓ <span>{{ number_format($funnel['pct_interested'], 1, ',', '.') }}%</span></div>
            <div class="funnel-step">
                <div class="stat-label">Interessados</div>
                <div class="stat-value" style="font-size:1.6rem;">{{ $funnel['interested'] }}</div>
            </div>
            <div class="funnel-arrow">↓ <span>{{ number_format($funnel['pct_contracts'], 1, ',', '.') }}%</span></div>
            <div class="funnel-step">
                <div class="stat-label">{{ $commercial::sales() }}</div>
                <div class="stat-value" style="font-size:1.6rem;">{{ $funnel['contracts'] }}</div>
            </div>
        </div>
        <p class="header-meta" style="margin:.75rem 0 0;">Percentuais = conversão da etapa anterior.</p>
    </div>

    <div class="grid grid-4" style="margin-bottom:1rem;">
        <div class="card">
            <div class="stat-label">Casas visitadas</div>
            <div class="stat-value">{{ $metrics->visits_total }}</div>
            <div class="header-meta">Visitas no período</div>
        </div>
        <div class="card">
            <div class="stat-label">Novos pontos</div>
            <div class="stat-value">{{ $metrics->properties_total }}</div>
            <div class="header-meta">Cadastros no período</div>
        </div>
        <div class="card">
            <div class="stat-label">Interessados</div>
            <div class="stat-value">{{ $metrics->interested_total }}</div>
        </div>
        <div class="card">
            <div class="stat-label">{{ $commercial::salesWon() }}</div>
            <div class="stat-value">{{ $metrics->installations_total }}</div>
            <div class="header-meta">{{ $commercial::salesRequestedMeta() }}</div>
        </div>
    </div>

    <div class="grid grid-{{ $teamView ? '4' : '3' }}" style="margin-bottom:1rem;">
        <div class="card">
            <div class="stat-label">Taxa de conversão</div>
            <div class="stat-value">{{ number_format($metrics->conversion_rate, 1, ',', '.') }}%</div>
            <div class="header-meta">{{ $commercial::salesPerVisits() }}</div>
        </div>
        <div class="card">
            <div class="stat-label">Retornos pendentes</div>
            <div class="stat-value">{{ $metrics->pending_follow_ups }}</div>
        </div>
        @if($teamView)
            <div class="card">
                <div class="stat-label">Vendedores ativos</div>
                <div class="stat-value">{{ $productivity['active_sellers'] }}</div>
                <div class="header-meta">Com atividade no período</div>
            </div>
            <div class="card">
                <div class="stat-label">Melhor vendedor</div>
                <div class="stat-value" style="font-size:1.05rem;">
                    {{ $productivity['best_seller']['name'] ?? '—' }}
                </div>
                @if($productivity['best_seller'])
                    <div class="header-meta">
                        {{ $productivity['best_seller']['installations'] }} {{ mb_strtolower($commercial::sales()) }} ·
                        {{ number_format($productivity['best_seller']['conversion_rate'], 1, ',', '.') }}%
                    </div>
                @endif
            </div>
        @else
            <div class="card">
                <div class="stat-label">Sua conversão</div>
                <div class="stat-value">{{ number_format($metrics->conversion_rate, 1, ',', '.') }}%</div>
            </div>
        @endif
    </div>

    @if($teamView)
        <div class="grid grid-2" style="margin-bottom:1rem;">
            <div class="card">
                <div class="stat-label">Média visitas / vendedor</div>
                <div class="stat-value">{{ number_format($productivity['avg_visits'], 1, ',', '.') }}</div>
            </div>
            <div class="card">
                <div class="stat-label">{{ $commercial::avgSalesPerSeller() }}</div>
                <div class="stat-value">{{ number_format($productivity['avg_contracts'], 1, ',', '.') }}</div>
            </div>
        </div>
    @endif

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <h2 style="margin-top:0; font-size:1.05rem;">Visitas por período</h2>
            <canvas id="chart-visits-period" height="140"></canvas>
        </div>
        <div class="card">
            <h2 style="margin-top:0; font-size:1.05rem;">Resultados por status</h2>
            <canvas id="chart-visits-status" height="140"></canvas>
        </div>
    </div>

    @if($teamView)
        <div class="grid grid-2">
            <div class="card">
                <h2 style="margin-top:0; font-size:1.05rem;">Ranking de vendedores</h2>
                <canvas id="chart-sellers" height="160"></canvas>
                <table class="table" style="margin-top:1rem;">
                    <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th>Visitas</th>
                        <th>Interessados</th>
                        <th>{{ $commercial::sales() }}</th>
                        <th>Conversão %</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($metrics->seller_productivity as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['visits'] }}</td>
                            <td>{{ $row['interested'] ?? 0 }}</td>
                            <td>{{ $row['installations'] }}</td>
                            <td>{{ number_format($row['conversion_rate'] ?? 0, 1, ',', '.') }}%</td>
                            <td>
                                <a class="btn btn-ghost" style="padding:.4rem .7rem; font-size:.8rem;"
                                   href="{{ route('map.index', ['user_id' => $row['user_id']]) }}">Abrir mapa</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Sem dados de produtividade.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2 style="margin-top:0; font-size:1.05rem;">Desempenho por região</h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Setor</th>
                        <th>Casas</th>
                        <th>Visitas</th>
                        <th>Interess.</th>
                        <th>{{ $commercial::sales() }}</th>
                        <th>Conv. %</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($metrics->sector_performance as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['properties_worked'] ?? 0 }}</td>
                            <td>{{ $row['visits'] }}</td>
                            <td>{{ $row['interested'] }}</td>
                            <td>{{ $row['installations'] ?? 0 }}</td>
                            <td>{{ number_format($row['conversion_rate'] ?? 0, 1, ',', '.') }}%</td>
                            <td>
                                @if(! empty($row['sector_id']))
                                    <a class="btn btn-ghost" style="padding:.4rem .7rem; font-size:.8rem;"
                                       href="{{ route('map.index', ['sector_id' => $row['sector_id']]) }}">Mapa</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Sem dados por região.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <style>
        .funnel-row {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .5rem;
            align-items: center;
        }
        .funnel-step {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: .85rem;
            padding: .85rem .7rem;
            text-align: center;
        }
        .funnel-arrow {
            text-align: center;
            color: #94a3b8;
            font-weight: 700;
            font-size: .85rem;
        }
        .funnel-arrow span { display: block; color: #38bdf8; font-size: .78rem; }
        @media (max-width: 900px) {
            .funnel-row { grid-template-columns: 1fr; }
            .funnel-arrow { transform: none; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const period = @json($metrics->visits_by_period);
            const byStatus = @json($metrics->visits_by_status);
            const ranking = @json($metrics->seller_ranking);
            const statusLabels = @json($statusLabels);
            const teamView = @json($teamView);

            const textColor = '#9AA3B5';
            const gridColor = 'rgba(42, 49, 66, 0.8)';
            const commonOptions = {
                responsive: true,
                plugins: { legend: { labels: { color: textColor } } },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } },
                },
            };

            new Chart(document.getElementById('chart-visits-period'), {
                type: 'line',
                data: {
                    labels: period.map((row) => row.date),
                    datasets: [{
                        label: 'Visitas',
                        data: period.map((row) => row.total),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        tension: 0.3,
                        fill: true,
                    }],
                },
                options: commonOptions,
            });

            const statusKeys = Object.keys(byStatus);
            new Chart(document.getElementById('chart-visits-status'), {
                type: 'doughnut',
                data: {
                    labels: statusKeys.map((key) => statusLabels[key] || key),
                    datasets: [{
                        data: statusKeys.map((key) => byStatus[key]),
                        backgroundColor: ['#3B82F6', '#22C55E', '#F59E0B', '#EF4444', '#9CA3AF', '#A855F7'],
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { color: textColor } } },
                },
            });

            if (teamView && document.getElementById('chart-sellers')) {
                new Chart(document.getElementById('chart-sellers'), {
                    type: 'bar',
                    data: {
                        labels: ranking.map((row) => row.name),
                        datasets: [
                            {
                                label: @json($commercial::sales()),
                                data: ranking.map((row) => row.installations),
                                backgroundColor: '#22C55E',
                            },
                            {
                                label: 'Visitas',
                                data: ranking.map((row) => row.visits),
                                backgroundColor: '#3B82F6',
                            },
                        ],
                    },
                    options: commonOptions,
                });
            }

            const citySelect = document.getElementById('city_id');
            const sectorSelect = document.getElementById('sector_id');
            if (citySelect && sectorSelect) {
                const sectorOptions = Array.from(sectorSelect.options).slice(1);
                function filterSectors() {
                    const cityId = citySelect.value;
                    const current = sectorSelect.value;
                    sectorSelect.innerHTML = '<option value="">Todos</option>';
                    sectorOptions.forEach((option) => {
                        if (!cityId || option.dataset.cityId === cityId) {
                            sectorSelect.appendChild(option.cloneNode(true));
                        }
                    });
                    if (current && Array.from(sectorSelect.options).some((o) => o.value === current)) {
                        sectorSelect.value = current;
                    }
                }
                citySelect.addEventListener('change', filterSectors);
                filterSectors();
            }
        })();
    </script>
@endsection
