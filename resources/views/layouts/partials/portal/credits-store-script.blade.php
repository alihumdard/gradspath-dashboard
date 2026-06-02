<script>
  (function () {
    const creditsBox = document.getElementById('portalCreditsBox');
    const creditsValue = document.getElementById('portalCreditsValue');

    if (!creditsBox || !creditsValue) {
      return;
    }

    const balanceUrl = creditsBox.dataset.balanceUrl;
    let refreshHandle = null;

    async function refreshCredits() {
      try {
        const response = await fetch(balanceUrl, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          credentials: 'same-origin',
        });

        if (!response.ok) {
          return;
        }

        const payload = await response.json();

        if (typeof payload.balance === 'number') {
          creditsValue.textContent = payload.balance;
        }
      } catch (error) {
        console.debug('Unable to refresh credits right now.', error);
      }
    }

    refreshCredits();
    refreshHandle = window.setInterval(refreshCredits, 30000);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        refreshCredits();
      }
    });

    window.addEventListener('beforeunload', () => {
      if (refreshHandle) {
        window.clearInterval(refreshHandle);
      }
    });
  })();
</script>
