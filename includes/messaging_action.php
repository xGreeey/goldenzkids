<?php
declare(strict_types=1);

/**
 * Shared POST handlers for messaging thread actions (admin + guard portals).
 */

function messaging_action_handle(PDO $conn, string $actorId, int $actorRole): void
{
    require_once __DIR__ . '/messaging_ajax.php';

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        messaging_ajax_json(['ok' => false, 'error' => 'Method not allowed.'], 405);
    }

    csrf_verify();

    $action = trim((string) ($_POST['action'] ?? ''));
    $peerId = trim((string) ($_POST['peer_id'] ?? ''));
    $groupId = (int) ($_POST['group_id'] ?? 0);

    $ok = false;
    $message = '';
    $title = 'Done';
    $type = 'success';
    $redirectIdle = false;

    switch ($action) {
        case 'clear_direct':
            if ($peerId === '') {
                messaging_ajax_json(['ok' => false, 'error' => 'No conversation selected.'], 400);
            }
            $ok = internal_messaging_delete_thread($conn, $actorId, $actorRole, $peerId);
            $message = $ok
                ? 'Direct message history cleared.'
                : 'Could not clear direct message history.';
            $title = $ok ? 'History cleared' : 'Action failed';
            $type = $ok ? 'success' : 'error';
            break;

        case 'clear_group':
            if ($groupId < 1) {
                messaging_ajax_json(['ok' => false, 'error' => 'No group selected.'], 400);
            }
            $ok = group_messaging_clear_history($conn, $groupId, $actorId);
            $message = $ok
                ? 'Group message history cleared.'
                : 'Could not clear group history.';
            $title = $ok ? 'History cleared' : 'Action failed';
            $type = $ok ? 'success' : 'error';
            break;

        case 'leave_group':
            if ($groupId < 1) {
                messaging_ajax_json(['ok' => false, 'error' => 'No group selected.'], 400);
            }
            $ok = group_messaging_leave_group($conn, $groupId, $actorId);
            $message = $ok
                ? 'You left the group.'
                : 'Could not leave the group.';
            $title = $ok ? 'Left group' : 'Action failed';
            $type = $ok ? 'success' : 'error';
            $redirectIdle = $ok;
            break;

        case 'delete_group':
            if ($groupId < 1) {
                messaging_ajax_json(['ok' => false, 'error' => 'No group selected.'], 400);
            }
            $ok = group_messaging_delete_group($conn, $groupId, $actorId, $actorRole);
            $message = $ok
                ? 'Group chat deleted.'
                : 'Could not delete the group.';
            $title = $ok ? 'Group deleted' : 'Action failed';
            $type = $ok ? 'success' : 'error';
            $redirectIdle = $ok;
            break;

        default:
            messaging_ajax_json(['ok' => false, 'error' => 'Unknown action.'], 400);
    }

    messaging_ajax_json([
        'ok' => $ok,
        'message' => $message,
        'title' => $title,
        'type' => $type,
        'redirect_idle' => $redirectIdle,
        'reload_thread' => $ok && !$redirectIdle && in_array($action, ['clear_direct', 'clear_group'], true),
        'peer_id' => $peerId,
        'group_id' => $groupId,
    ]);
}
