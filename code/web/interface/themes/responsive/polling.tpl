{if !empty($loggedIn)}
    {if array_key_exists('Community Engagement', $enabledModules)}
        <script type="text/javascript">
            AspenDiscovery.ToastNotifications.startPolling({
                endpoint: '/MyAccount/AJAX?method=CommunityEngagementPoll'
            });
        </script>
    {/if}
{/if}