{if !empty($loggedIn)}
	{if array_key_exists('Community Engagement', $enabledModules)}
		<script type="text/javascript">
			AspenDiscovery.ToastNotifications.listenToSSE(
				{
					eventSource: '/MyAccount/AJAX?method=CommunityEngagementSSE',
					eventName: 'ce_notification',
				}
			);
		</script>
	{/if}
{/if}
