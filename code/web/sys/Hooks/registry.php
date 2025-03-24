<?php

$hook_actions = array();

/**
 * Attach (or remove) multiple callbacks to an action and trigger those callbacks when that action is called.
 *
 * @param string $hook name
 * @param string $action name
 * @param mixed $callback the method or function to call - FALSE to remove all callbacks for action
 */

function add_action($hook_name, $action_name, $callback = NULL)
{
	global $hook_actions;

	if ($callback) {
		$hook_actions[$hook_name][$action_name] = $callback;
	} else {
		unset($hook_actions[$hook_name][$action_name]);
	}
}

/**
 * Executes all callback functions attached to a given action.
 *
 * @param string $action The name of the action to execute.
 * @param mixed $value (optional) The value to pass to each callback function. Defaults to NULL.
 * @return void
 */

function do_action($hook_name, $value = NULL)
{
	global $hook_actions;

	if (isset($hook_actions[$hook_name])) // Fire a callback
	{
		foreach ($hook_actions[$hook_name] as $function) {
			call_user_func($function, $value);
		}
	}
}