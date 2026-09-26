/**
 * Social CV interactions (OUTSIDE item 1 follow-up).
 *
 * Guarded to the CV page only. Copy uses the async clipboard API with a
 * prompt() fallback; Share uses the Web Share API with a clipboard
 * fallback; Download prints (the print stylesheet yields a clean CV the
 * user can save as PDF). No backend calls.
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        } else {
            fn();
        }
    }

    onReady(function () {
        if (!document.querySelector('.social-cv-container')) {
            return;
        }

        function profileUrl() {
            var input = document.querySelector('.url-input-group input');
            return input ? input.value : window.location.href;
        }

        function copyText(text, done) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    done(true);
                }, function () {
                    done(false);
                });
            } else {
                var ok = false;
                try {
                    var area = document.createElement('textarea');
                    area.value = text;
                    document.body.appendChild(area);
                    area.select();
                    ok = document.execCommand('copy');
                    document.body.removeChild(area);
                } catch (err) {
                    ok = false;
                }
                done(ok);
            }
        }

        document.querySelectorAll('.btn-copy').forEach(function (btn) {
            // The endorsement remove form also uses .btn-copy: only copy
            // for the footer URL button, never submit that form by hijack.
            if (btn.getAttribute('type') === 'submit') {
                return;
            }
            btn.addEventListener('click', function () {
                var original = btn.innerHTML;
                copyText(profileUrl(), function (ok) {
                    btn.textContent = ok ? 'Copied' : 'Copy failed';
                    window.setTimeout(function () {
                        btn.innerHTML = original;
                    }, 1600);
                });
            });
        });

        document.querySelectorAll('.btn-share').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var original = btn.innerHTML;
                var data = {
                    title: document.title,
                    text: 'YouthNexus Social CV',
                    url: profileUrl(),
                };
                if (navigator.share) {
                    navigator.share(data).catch(function () {});
                } else {
                    copyText(data.url, function (ok) {
                        btn.textContent = ok ? 'Link copied' : 'Copy failed';
                        window.setTimeout(function () {
                            btn.innerHTML = original;
                        }, 1600);
                    });
                }
            });
        });

        document.querySelectorAll('.btn-download-pdf, .btn-download-pdf-large').forEach(function (btn) {
            btn.addEventListener('click', function () {
                window.print();
            });
        });
    });
})();
