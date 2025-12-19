<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocuSign Webhook Logs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        h1 {
            color: #2d3748;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #718096;
            font-size: 16px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            color: #2d3748;
            font-size: 28px;
            font-weight: bold;
        }

        .webhook-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .webhook-header {
            padding: 20px;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .webhook-header:hover {
            background: #edf2f7;
        }

        .webhook-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            color: #718096;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .meta-value {
            color: #2d3748;
            font-size: 14px;
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background: #feebc8;
            color: #7c2d12;
        }

        .badge-info {
            background: #bee3f8;
            color: #2c5282;
        }

        .webhook-body {
            padding: 20px;
            background: #1a202c;
            display: none;
        }

        .webhook-body.active {
            display: block;
        }

        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }

        .no-webhooks {
            background: white;
            padding: 60px 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .no-webhooks-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .no-webhooks-text {
            color: #718096;
            font-size: 18px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 16px;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #2d3748;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .pagination .active {
            background: #667eea;
            color: white;
        }

        .toggle-icon {
            float: right;
            transition: transform 0.3s;
        }

        .toggle-icon.rotated {
            transform: rotate(180deg);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📨 Signature Webhooks</h1>
            <p class="subtitle">Monitor and analyze incoming signature provider webhook events</p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Total Webhooks</div>
                <div class="stat-value">{{ $signatures->total() }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ \App\Models\Signature::whereNotNull('signed_at')->count() }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value">
                    {{ \App\Models\Signature::whereNull('signed_at')->whereNotNull('webhook_payload')->count() }}
                </div>
            </div>
        </div>

        @if($signatures->count() > 0)
            @foreach($signatures as $signature)
                <div class="webhook-card">
                    <div class="webhook-header" onclick="toggleWebhook({{ $signature->id }})">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="font-size: 18px; color: #2d3748;">
                                    Webhook #{{ $signature->id }}
                                </strong>
                                <span class="badge badge-info">{{ ucfirst($signature->provider) }}</span>
                                @if($signature->signed_at)
                                    <span class="badge badge-success">Completed</span>
                                @else
                                    <span class="badge badge-warning">Pending</span>
                                @endif
                                @if($signature->provider_status)
                                    <span class="badge badge-info">{{ $signature->provider_status }}</span>
                                @endif
                            </div>
                            <span class="toggle-icon" id="icon-{{ $signature->id }}">▼</span>
                        </div>

                        <div class="webhook-meta">
                            @if($signature->provider_envelope_id)
                                <div class="meta-item">
                                    <span class="meta-label">Envelope ID</span>
                                    <span class="meta-value">{{ $signature->provider_envelope_id }}</span>
                                </div>
                            @endif
                            <div class="meta-item">
                                <span class="meta-label">Received At</span>
                                <span
                                    class="meta-value">{{ $signature->webhook_received_at ? $signature->webhook_received_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Time Ago</span>
                                <span
                                    class="meta-value">{{ $signature->webhook_received_at ? $signature->webhook_received_at->diffForHumans() : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="webhook-body" id="webhook-{{ $signature->id }}">
                        <div style="margin-bottom: 10px; color: #e2e8f0; font-size: 14px;">
                            <strong>Signable:</strong> {{ str_replace('App\\Models\\', '', $signature->signable_type) }}
                            ({{ $signature->signable_uuid }})
                        </div>
                        <pre>{{ json_encode($signature->webhook_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            @endforeach

            <div class="pagination">
                {{ $signatures->links() }}
            </div>
        @else
            <div class="no-webhooks">
                <div class="no-webhooks-icon">📭</div>
                <p class="no-webhooks-text">No webhooks received yet</p>
                <p class="no-webhooks-text" style="margin-top: 10px; font-size: 14px;">
                    Configure your signature provider to send webhooks to:<br>
                    <code
                        style="background: #f7fafc; padding: 8px 16px; border-radius: 6px; margin-top: 10px; display: inline-block;">
                                    {{ url('/api/signature/webhook/{provider}') }}
                                </code>
                </p>
            </div>
        @endif
    </div>

    <script>
        function toggleWebhook(id) {
            const body = document.getElementById('webhook-' + id);
            const icon = document.getElementById('icon-' + id);

            body.classList.toggle('active');
            icon.classList.toggle('rotated');
        }
    </script>
</body>

</html>