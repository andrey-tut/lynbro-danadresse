/**
 * Danadresse — settings-page test lookup (Status tab).
 */
(function () {
	'use strict';

	var cfg = window.lynbroDanadresseAdmin || {};
	var input = document.getElementById('danadresse-test-q');
	var out = document.getElementById('danadresse-test-out');
	var btnAc = document.getElementById('danadresse-test-ac');
	var btnDv = document.getElementById('danadresse-test-dv');

	if (!input || !out) {
		return;
	}

	function say(text) {
		out.textContent = text;
	}

	function run(mode) {
		var q = input.value.trim();
		if (!q) {
			say(cfg.labels.empty);
			return;
		}
		say(cfg.labels.working);

		var body = new URLSearchParams();
		body.set('action', 'lynbro_danadresse_admin_test');
		body.set('nonce', cfg.nonce || '');
		body.set('mode', mode);
		body.set('q', q);

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
			body: body.toString()
		}).then(function (res) {
			return res.json();
		}).then(function (json) {
			if (!json || !json.success) {
				say((json && json.data && json.data.message) ? json.data.message : 'Error');
				return;
			}
			if (mode === 'datavask') {
				var a = json.data.adresse || {};
				var pretty = [a.vejnavn, a.husnr, a.etage, a['dør']].filter(Boolean).join(' ');
				var tail = [a.postnr, a.postnrnavn].filter(Boolean).join(' ');
				say('Kategori ' + json.data.kategori + (pretty ? ' — ' + pretty + (tail ? ', ' + tail : '') : ''));
				return;
			}
			var list = json.data.items || [];
			if (!list.length) {
				say(cfg.labels.none);
				return;
			}
			out.textContent = '';
			var ul = document.createElement('ul');
			list.forEach(function (item) {
				var li = document.createElement('li');
				li.textContent = item.forslagstekst || item.tekst;
				ul.appendChild(li);
			});
			out.appendChild(ul);
		}).catch(function () {
			say('Network error');
		});
	}

	if (btnAc) {
		btnAc.addEventListener('click', function () { run('autocomplete'); });
	}
	if (btnDv) {
		btnDv.addEventListener('click', function () { run('datavask'); });
	}
	input.addEventListener('keydown', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			run('autocomplete');
		}
	});
})();
