<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Communication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Contrôleur pour la gestion des communications dans le CRM
 *
 * Permet de visualiser, filtrer et analyser toutes les communications
 * (emails, SMS, WhatsApp, etc.) envoyées aux clients et utilisateurs.
 */
class CommunicationController extends Controller
{
    /**
     * Afficher la liste des communications avec filtres
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $query = Communication::query()
            ->with(['communicable', 'handledBy'])
            ->latest('sent_at');

        // Filtre par canal
        if ($request->filled('channel')) {
            $query->byChannel($request->channel);
        }

        // Filtre par direction
        if ($request->filled('direction')) {
            if ($request->direction === 'inbound') {
                $query->inbound();
            } elseif ($request->direction === 'outbound') {
                $query->outbound();
            }
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Filtre par type d'entité
        if ($request->filled('entity_type')) {
            if ($request->entity_type === 'client') {
                $query->forClient();
            } elseif ($request->entity_type === 'user') {
                $query->forUser();
            }
        }

        // Filtre par plage de dates
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->betweenDates($request->date_from, $request->date_to);
        } elseif ($request->filled('date_from')) {
            $query->where('sent_at', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('sent_at', '<=', $request->date_to);
        }

        // Recherche par sujet ou message
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Filtre par provider
        if ($request->filled('provider')) {
            $query->byProvider($request->provider);
        }

        $communications = $query->paginate($request->get('per_page', 25));

        // Enrichir les données pour l'affichage
        $communications->getCollection()->transform(function ($comm) {
            return $this->transformCommunication($comm);
        });

        // Si requête API (JSON)
        if ($request->wantsJson()) {
            return response()->json([
                'data' => $communications,
                'filters' => [
                    'channels' => Communication::getAvailableChannels(),
                    'directions' => Communication::getAvailableDirections(),
                    'statuses' => Communication::getAvailableStatuses(),
                ],
            ]);
        }

        // Rendu Inertia pour le CRM
        return Inertia::render('CRM/Communications/Index', [
            'communications' => $communications,
            'filters' => $request->only([
                'channel', 'direction', 'status', 'entity_type',
                'date_from', 'date_to', 'search', 'provider'
            ]),
            'availableFilters' => [
                'channels' => Communication::getAvailableChannels(),
                'directions' => Communication::getAvailableDirections(),
                'statuses' => Communication::getAvailableStatuses(),
            ],
        ]);
    }

    /**
     * Afficher le détail d'une communication
     */
    public function show(Request $request, int $id): InertiaResponse|JsonResponse
    {
        $communication = Communication::with(['communicable', 'handledBy', 'files'])
            ->findOrFail($id);

        $data = $this->transformCommunication($communication, true);

        if ($request->wantsJson()) {
            return response()->json(['data' => $data]);
        }

        return Inertia::render('CRM/Communications/Show', [
            'communication' => $data,
        ]);
    }

    /**
     * Afficher les communications d'un client spécifique
     */
    public function forClient(Request $request, int $clientId): InertiaResponse|JsonResponse
    {
        $client = Client::findOrFail($clientId);

        $query = Communication::forClient($clientId)
            ->with(['handledBy'])
            ->latest('sent_at');

        // Appliquer les mêmes filtres que index
        if ($request->filled('channel')) {
            $query->byChannel($request->channel);
        }

        $communications = $query->paginate($request->get('per_page', 25));

        $communications->getCollection()->transform(function ($comm) {
            return $this->transformCommunication($comm);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'client' => [
                    'id' => $client->id,
                    'name' => $client->full_name,
                    'email' => $client->email,
                ],
                'communications' => $communications,
            ]);
        }

        return Inertia::render('CRM/Communications/ForClient', [
            'client' => $client,
            'communications' => $communications,
        ]);
    }

    /**
     * Afficher les communications d'un utilisateur spécifique
     */
    public function forUser(Request $request, int $userId): InertiaResponse|JsonResponse
    {
        $user = User::findOrFail($userId);

        $query = Communication::forUser($userId)
            ->with(['handledBy'])
            ->latest('sent_at');

        if ($request->filled('channel')) {
            $query->byChannel($request->channel);
        }

        $communications = $query->paginate($request->get('per_page', 25));

        $communications->getCollection()->transform(function ($comm) {
            return $this->transformCommunication($comm);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'communications' => $communications,
            ]);
        }

        return Inertia::render('CRM/Communications/ForUser', [
            'user' => $user,
            'communications' => $communications,
        ]);
    }

    /**
     * Afficher les statistiques des communications
     */
    public function stats(Request $request): JsonResponse
    {
        // Période par défaut : 30 derniers jours
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Stats par canal
        $byChannel = Communication::query()
            ->whereBetween('sent_at', [$startDate, $endDate])
            ->select('channel', DB::raw('count(*) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->toArray();

        // Stats par direction
        $byDirection = Communication::query()
            ->whereBetween('sent_at', [$startDate, $endDate])
            ->select('direction', DB::raw('count(*) as total'))
            ->groupBy('direction')
            ->pluck('total', 'direction')
            ->toArray();

        // Stats par statut
        $byStatus = Communication::query()
            ->whereBetween('sent_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Stats par provider
        $byProvider = Communication::query()
            ->whereBetween('sent_at', [$startDate, $endDate])
            ->whereNotNull('provider')
            ->select('provider', DB::raw('count(*) as total'))
            ->groupBy('provider')
            ->pluck('total', 'provider')
            ->toArray();

        // Évolution par jour
        $dailyStats = Communication::query()
            ->whereBetween('sent_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(sent_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('SUM(CASE WHEN status = "sent" OR status = "delivered" THEN 1 ELSE 0 END) as success'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Totaux
        $totals = [
            'total' => Communication::whereBetween('sent_at', [$startDate, $endDate])->count(),
            'sent' => Communication::whereBetween('sent_at', [$startDate, $endDate])->sent()->count(),
            'failed' => Communication::whereBetween('sent_at', [$startDate, $endDate])->failed()->count(),
            'today' => Communication::today()->count(),
            'this_week' => Communication::thisWeek()->count(),
            'this_month' => Communication::thisMonth()->count(),
        ];

        // Taux de succès
        $successRate = $totals['total'] > 0
            ? round(($totals['sent'] / $totals['total']) * 100, 2)
            : 0;

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'totals' => $totals,
            'success_rate' => $successRate,
            'by_channel' => $byChannel,
            'by_direction' => $byDirection,
            'by_status' => $byStatus,
            'by_provider' => $byProvider,
            'daily_stats' => $dailyStats,
        ]);
    }

    /**
     * Exporter les communications en CSV
     */
    public function export(Request $request)
    {
        $query = Communication::query()
            ->with(['communicable'])
            ->latest('sent_at');

        // Appliquer les filtres
        if ($request->filled('channel')) {
            $query->byChannel($request->channel);
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->betweenDates($request->date_from, $request->date_to);
        }

        $communications = $query->limit(10000)->get();

        $filename = 'communications_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($communications) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // En-têtes CSV
            fputcsv($file, [
                'ID', 'Date', 'Canal', 'Direction', 'Statut',
                'Destinataire', 'Sujet', 'Provider', 'Tentatives'
            ], ';');

            foreach ($communications as $comm) {
                $destinataire = '';
                if ($comm->communicable) {
                    $destinataire = $comm->communicable->email
                        ?? $comm->communicable->name
                        ?? $comm->communicable->full_name
                        ?? '';
                }

                fputcsv($file, [
                    $comm->id,
                    $comm->sent_at?->format('d/m/Y H:i'),
                    $comm->channel_label,
                    $comm->direction_label,
                    $comm->status_label,
                    $destinataire,
                    $comm->subject,
                    $comm->provider,
                    $comm->retry_count,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Renvoyer une communication en échec
     */
    public function resend(Request $request, int $id): JsonResponse
    {
        $communication = Communication::findOrFail($id);

        if (!$communication->canBeResent()) {
            return response()->json([
                'success' => false,
                'message' => __('Cette communication ne peut pas être renvoyée.'),
            ], 422);
        }

        // TODO: Implémenter la logique de renvoi selon le type de notification
        // Cela nécessiterait de stocker les données de la notification originale

        return response()->json([
            'success' => false,
            'message' => __('Fonctionnalité de renvoi en cours de développement.'),
        ], 501);
    }

    /**
     * Transformer une communication pour l'affichage
     */
    protected function transformCommunication(Communication $communication, bool $includeDetails = false): array
    {
        $data = [
            'id' => $communication->id,
            'uuid' => $communication->uuid,
            'channel' => $communication->channel,
            'channel_label' => $communication->channel_label,
            'channel_icon' => $communication->channel_icon,
            'channel_badge_color' => $communication->channel_badge_color,
            'direction' => $communication->direction,
            'direction_label' => $communication->direction_label,
            'direction_icon' => $communication->direction_icon,
            'status' => $communication->status,
            'status_label' => $communication->status_label,
            'status_icon' => $communication->status_icon,
            'status_badge_color' => $communication->status_badge_color,
            'subject' => $communication->subject,
            'message_excerpt' => $communication->getMessageExcerpt(150),
            'sent_at' => $communication->sent_at?->toIso8601String(),
            'formatted_date' => $communication->formatted_date,
            'relative_date' => $communication->relative_date,
            'provider' => $communication->provider,
            'retry_count' => $communication->retry_count,
            'can_be_resent' => $communication->canBeResent(),
        ];

        // Informations sur le destinataire
        if ($communication->communicable) {
            $data['recipient'] = [
                'type' => class_basename($communication->communicable_type),
                'id' => $communication->communicable_id,
                'name' => $communication->communicable->name
                    ?? $communication->communicable->full_name
                    ?? 'N/A',
                'email' => $communication->communicable->email ?? null,
            ];
        }

        // Informations sur le gestionnaire
        if ($communication->handledBy) {
            $data['handled_by'] = [
                'id' => $communication->handledBy->id,
                'name' => $communication->handledBy->name,
            ];
        }

        // Détails supplémentaires si demandés
        if ($includeDetails) {
            $data['message'] = $communication->message;
            $data['notification_type'] = $communication->notification_type;
            $data['metadata'] = $communication->metadata;
            $data['failed_at'] = $communication->failed_at?->toIso8601String();
            $data['has_attachments'] = $communication->hasAttachments();
            $data['attachments_count'] = $communication->getAttachmentsCount();

            if ($communication->relationLoaded('files')) {
                $data['files'] = $communication->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'name' => $file->original_name ?? $file->name,
                        'size' => $file->size,
                        'type' => $file->mime_type,
                    ];
                });
            }
        }

        return $data;
    }
}
