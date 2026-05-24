<?php
/**
 * includes/task_validation.php
 *
 * Shared validation helpers for task add/edit forms.
 * Include after require_once '../config/database.php'.
 *
 * Provides:
 *   TASK_ALLOWED_PRIORITIES  — array of valid priority values
 *   TASK_ALLOWED_STATUSES    — array of valid status values
 *   validateTaskInput($post) — returns ['error' => string|'', 'data' => array]
 */

const TASK_ALLOWED_PRIORITIES = ['low', 'medium', 'high'];
const TASK_ALLOWED_STATUSES   = ['pending', 'in_progress', 'completed'];

/**
 * Validate and sanitize POST data for a task form.
 *
 * @param  array $post  Typically $_POST
 * @param  array $defaults  Default values when a field is absent (useful for edit)
 * @return array{error: string, data: array}
 *   - error: non-empty string if validation fails
 *   - data:  sanitized field values (title, description, priority, status, due_date, due_val)
 */
function validateTaskInput(array $post, array $defaults = []): array
{
    $title       = sanitize($post['title']       ?? ($defaults['title']       ?? ''));
    $description = sanitize($post['description'] ?? ($defaults['description'] ?? ''));
    $priority    = sanitize($post['priority']    ?? ($defaults['priority']    ?? 'medium'));
    $status      = sanitize($post['status']      ?? ($defaults['status']      ?? 'pending'));
    $due_date    = sanitize($post['due_date']     ?? ($defaults['due_date']    ?? ''));

    if (empty($title)) {
        $error = 'Task title is required.';
    } elseif (strlen($title) > 200) {
        $error = 'Title must be under 200 characters.';
    } elseif (!in_array($priority, TASK_ALLOWED_PRIORITIES)) {
        $error = 'Invalid priority value.';
    } elseif (!in_array($status, TASK_ALLOWED_STATUSES)) {
        $error = 'Invalid status value.';
    } elseif ($due_date && !DateTime::createFromFormat('Y-m-d', $due_date)) {
        $error = 'Invalid due date format.';
    } else {
        $error = '';
    }

    return [
        'error' => $error,
        'data'  => [
            'title'       => $title,
            'description' => $description,
            'priority'    => $priority,
            'status'      => $status,
            'due_date'    => $due_date,
            'due_val'     => $due_date ?: null,   // null-safe value for DB binding
        ],
    ];
}
