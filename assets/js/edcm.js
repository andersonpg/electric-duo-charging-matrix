/*
 * Electric Duo EV Charging Matrix - progressive enhancement.
 *
 * The matrix is complete, crawlable HTML without this script. This file adds:
 * tabs, search + filters, column picker, detail dialogs, keyboard navigation
 * across the grid, CSV export and a full screen mode.
 * No dependencies. All dialog content is built with textContent (no innerHTML).
 */
(function () {
	'use strict';

	if (!document.querySelector || !Element.prototype.closest) {
		return;
	}

	var SVGNS = 'http://www.w3.org/2000/svg';
	var ICONS = { PC: 'edcm-i-pc', BOTH: 'edcm-i-car', APP: 'edcm-i-app', ROAM: 'edcm-i-roam', NO: 'edcm-i-no' };
	var GROUPS = ['PC', 'BOTH', 'APP', 'ROAM'];
	var FALLBACK = {
		showing: 'Showing {shown} of {total} {plural}',
		integrated: 'Integrated with {n} of {total} networks',
		close: 'Close',
		previous: 'Previous',
		next: 'Next',
		nobody: 'No direct integrations listed.',
		officialSite: 'Official site',
		sources: 'Sources',
		fullscreen: 'Full screen',
		exitFullscreen: 'Exit full screen',
		notIntegrated: 'Not integrated',
		allFor: 'All networks for {name}',
		aboutNet: 'About {name}',
		whoWorksWith: 'Works with {name}',
		system: 'Charging system',
		oemApp: 'Automaker app',
		notes: 'Notes',
		csvName: 'ev-charging-matrix'
	};
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function qsa(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	function el(tag, cls, text) {
		var n = document.createElement(tag);
		if (cls) {
			n.className = cls;
		}
		if (text !== undefined && text !== null) {
			n.textContent = text;
		}
		return n;
	}

	function svg(id) {
		var s = document.createElementNS(SVGNS, 'svg');
		s.setAttribute('class', 'edcm-i');
		s.setAttribute('aria-hidden', 'true');
		s.setAttribute('focusable', 'false');
		s.setAttribute('width', '20');
		s.setAttribute('height', '20');
		var u = document.createElementNS(SVGNS, 'use');
		u.setAttribute('href', '#' + id);
		s.appendChild(u);
		return s;
	}

	function setHidden(node, hide) {
		if (!node) {
			return;
		}
		if (hide) {
			node.setAttribute('hidden', '');
		} else {
			node.removeAttribute('hidden');
		}
	}

	function cap(s) {
		return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
	}

	function raf(fn) {
		return (window.requestAnimationFrame || window.setTimeout)(fn, 16);
	}

	function init(root, index) {
		var inner = qs('.edcm__inner', root);
		var dialog = qs('.edcm__dialog', root);
		var dlg = qs('[data-dlg]', root);
		var i18n = {};
		try {
			i18n = JSON.parse(root.getAttribute('data-i18n') || '{}') || {};
		} catch (e) {
			i18n = {};
		}

		function t(key, vars) {
			var s = i18n[key] !== undefined ? i18n[key] : FALLBACK[key];
			s = s === undefined ? key : s;
			if (vars) {
				Object.keys(vars).forEach(function (k) {
					s = s.split('{' + k + '}').join(vars[k]);
				});
			}
			return s;
		}

		root.classList.add('edcm--js');

		/* ---------- read what the server rendered ---------- */

		var labels = {};
		qsa('.edcm__legendlist .edcm__pill', root).forEach(function (p) {
			var l = qs('.edcm__lbl', p);
			labels[p.getAttribute('data-code')] = l ? l.textContent : p.getAttribute('data-code');
		});
		if (!labels.NO) {
			labels.NO = t('notIntegrated');
		}

		var netInfo = {};
		qsa('.edcm__netinfo', root).forEach(function (a) {
			var link = qs('.edcm__netlink a', a);
			netInfo[a.getAttribute('data-net')] = {
				name: (qs('.edcm__neth', a) || {}).textContent || '',
				type: (qs('.edcm__nettype', a) || {}).textContent || '',
				about: (qs('.edcm__netabout', a) || {}).textContent || '',
				url: link ? link.href : '',
				sources: qsa('.edcm__netsrc a', a).map(function (s) {
					return { label: s.textContent, url: s.href };
				})
			};
		});

		var panels = qsa('.edcm__panel', root).map(function (el_) {
			var p = {
				el: el_,
				key: el_.getAttribute('data-sheet'),
				plural: el_.getAttribute('data-plural') || '',
				scroller: qs('.edcm__scroller', el_),
				table: qs('.edcm__table', el_),
				rows: qsa('tbody .edcm__row', el_),
				heads: {},
				netIds: [],
				hiddenCols: {},
				q: '',
				cat: '',
				net: '',
				cur: null
			};
			qsa('thead th.edcm__nethead', el_).forEach(function (th) {
				var id = th.getAttribute('data-net');
				p.heads[id] = th;
				p.netIds.push(id);
			});
			p.rows.forEach(function (r) {
				r._c = {};
				qsa('td.edcm__cell', r).forEach(function (td) {
					r._c[td.getAttribute('data-net')] = td;
				});
			});
			return p;
		});
		if (!panels.length) {
			return;
		}

		function panelOf(node) {
			var pe = node.closest('.edcm__panel');
			for (var i = 0; i < panels.length; i++) {
				if (panels[i].el === pe) {
					return panels[i];
				}
			}
			return panels[0];
		}

		function codeOf(row, id) {
			return row._c[id] ? row._c[id].getAttribute('data-code') : 'NO';
		}

		function detailOf(row, id) {
			var d = row._c[id] ? qs('.edcm__detail', row._c[id]) : null;
			return d ? d.textContent : '';
		}

		function isCard(p) {
			return !!p.table && window.getComputedStyle(p.table).display === 'block';
		}

		/* ---------- tabs ---------- */

		var tabs = qsa('.edcm__tab', root);
		var active = null;

		function activate(key, userInitiated) {
			var target = null;
			panels.forEach(function (p) {
				if (p.key === key) {
					target = p;
				}
			});
			if (!target) {
				target = panels[0];
			}
			active = target;
			panels.forEach(function (p) {
				setHidden(p.el, p !== target && tabs.length > 0);
			});
			tabs.forEach(function (tab) {
				var on = tab.getAttribute('data-sheet') === target.key;
				tab.setAttribute('aria-selected', on ? 'true' : 'false');
				tab.tabIndex = on ? 0 : -1;
			});
			refresh(target);
			if (userInitiated && index === 0 && window.history && history.replaceState) {
				try {
					history.replaceState(null, '', '#edcm-' + target.key);
				} catch (e) { /* ignore */ }
			}
		}

		tabs.forEach(function (tab, i) {
			tab.addEventListener('click', function () {
				activate(tab.getAttribute('data-sheet'), true);
			});
			tab.addEventListener('keydown', function (e) {
				var n = tabs.length;
				var to = -1;
				if (e.key === 'ArrowRight') {
					to = (i + 1) % n;
				} else if (e.key === 'ArrowLeft') {
					to = (i - 1 + n) % n;
				} else if (e.key === 'Home') {
					to = 0;
				} else if (e.key === 'End') {
					to = n - 1;
				}
				if (to > -1) {
					e.preventDefault();
					tabs[to].focus();
					activate(tabs[to].getAttribute('data-sheet'), true);
				}
			});
		});

		/* ---------- filtering ---------- */

		function applyFilters(p) {
			var tokens = p.q.toLowerCase().trim().split(/\s+/).filter(Boolean);
			var shown = 0;
			p.rows.forEach(function (r) {
				var ok = true;
				if (tokens.length) {
					var hay = r.getAttribute('data-search') || '';
					ok = tokens.every(function (tk) {
						return hay.indexOf(tk) !== -1;
					});
				}
				if (ok && p.cat) {
					ok = r.getAttribute('data-cat') === p.cat;
				}
				if (ok && p.net) {
					ok = codeOf(r, p.net) !== 'NO';
				}
				r.hidden = !ok;
				if (ok) {
					shown++;
				}
			});
			var count = qs('.edcm__count', p.el);
			if (count) {
				count.textContent = t('showing', { shown: shown, total: p.rows.length, plural: p.plural });
			}
			setHidden(qs('.edcm__empty', p.el), shown !== 0);
			refresh(p);
		}

		/* ---------- columns ---------- */

		function applyCols(p) {
			var vis = 0;
			p.netIds.forEach(function (id) {
				var h = !!p.hiddenCols[id];
				if (!h) {
					vis++;
				}
				setHidden(p.heads[id], h);
				p.rows.forEach(function (r) {
					setHidden(r._c[id], h);
				});
			});
			if (p.table) {
				p.table.style.setProperty('--edcm-cols', String(vis));
			}
			qsa('.edcm__collist input', p.el).forEach(function (cb) {
				cb.checked = !p.hiddenCols[cb.getAttribute('data-net')];
			});
			var cc = qs('.edcm__colcount', p.el);
			if (cc) {
				cc.textContent = vis + '/' + p.netIds.length;
			}
			refresh(p);
		}

		/* ---------- scroll buttons ---------- */

		function updateScroll(p) {
			var wrap = qs('.edcm__scrollbtns', p.el);
			var s = p.scroller;
			if (!wrap || !s || p.el.hidden) {
				return;
			}
			var overflow = s.scrollWidth - s.clientWidth > 2 && !isCard(p);
			wrap.hidden = !overflow;
			if (overflow) {
				var b = qsa('[data-scroll]', wrap);
				if (b[0]) {
					b[0].disabled = s.scrollLeft <= 1;
				}
				if (b[1]) {
					b[1].disabled = s.scrollLeft + s.clientWidth >= s.scrollWidth - 1;
				}
			}
		}

		/* ---------- keyboard model (roving tabindex across the grid) ---------- */

		function visible(p) {
			return {
				cols: p.netIds.filter(function (id) {
					return !p.hiddenCols[id];
				}),
				rows: p.rows.filter(function (r) {
					return !r.hidden;
				})
			};
		}

		function gridEl(p, g, r, c) {
			if (r < 0 || c < 0 || r > g.rows.length || c > g.cols.length) {
				return null;
			}
			if (r === 0) {
				return c === 0 ? null : qs('.edcm__netbtn', p.heads[g.cols[c - 1]]);
			}
			var row = g.rows[r - 1];
			if (c === 0) {
				return qs('.edcm__rowbtn', row);
			}
			return qs('.edcm__cellbtn', row._c[g.cols[c - 1]]);
		}

		function locate(p, g, node) {
			if (node.classList.contains('edcm__netbtn')) {
				return { r: 0, c: g.cols.indexOf(node.getAttribute('data-net')) + 1 };
			}
			var row = node.closest('.edcm__row');
			var r = g.rows.indexOf(row) + 1;
			if (node.classList.contains('edcm__rowbtn')) {
				return { r: r, c: 0 };
			}
			return { r: r, c: g.cols.indexOf(node.closest('td').getAttribute('data-net')) + 1 };
		}

		function updateStops(p) {
			if (p.el.hidden) {
				return;
			}
			// The pinned header copy lives outside the scroller and is never a tab stop.
			var all = qsa('.edcm__netbtn, .edcm__rowbtn, .edcm__cellbtn', p.scroller || p.el);
			if (isCard(p)) {
				all.forEach(function (b) {
					b.tabIndex = b.classList.contains('edcm__netbtn') ? -1 : 0;
				});
				return;
			}
			all.forEach(function (b) {
				b.tabIndex = -1;
			});
			var cur = p.cur;
			if (!cur || !p.el.contains(cur) || cur.closest('[hidden]')) {
				var first = p.rows.filter(function (r) {
					return !r.hidden;
				})[0];
				cur = first ? qs('.edcm__rowbtn', first) : qs('.edcm__netbtn:not([hidden])', p.scroller || p.el);
			}
			if (cur) {
				cur.tabIndex = 0;
				p.cur = cur;
			}
		}

		function onGridKey(p, e) {
			var node = e.target.closest('.edcm__netbtn, .edcm__rowbtn, .edcm__cellbtn');
			if (!node || !p.el.contains(node) || isCard(p) || e.altKey || e.metaKey) {
				return;
			}
			var g = visible(p);
			var pos = locate(p, g, node);
			var r = pos.r;
			var c = pos.c;
			var maxR = g.rows.length;
			var maxC = g.cols.length;
			switch (e.key) {
				case 'ArrowRight':
					c = Math.min(c + 1, maxC);
					break;
				case 'ArrowLeft':
					c = Math.max(c - 1, r === 0 ? 1 : 0);
					break;
				case 'ArrowDown':
					r = Math.min(r + 1, maxR);
					break;
				case 'ArrowUp':
					r = Math.max(r - 1, c === 0 ? 1 : 0);
					break;
				case 'PageDown':
					r = Math.min(r + 5, maxR);
					break;
				case 'PageUp':
					r = Math.max(r - 5, c === 0 ? 1 : 0);
					break;
				case 'Home':
					if (e.ctrlKey) {
						r = c === 0 ? 1 : 0;
					}
					c = r === 0 ? 1 : 0;
					break;
				case 'End':
					if (e.ctrlKey) {
						r = maxR;
					}
					c = maxC;
					break;
				default:
					return;
			}
			var next = gridEl(p, g, r, c);
			if (!next) {
				return;
			}
			e.preventDefault();
			if (p.cur && p.cur !== next) {
				p.cur.tabIndex = -1;
			}
			next.tabIndex = 0;
			p.cur = next;
			next.focus();
		}

		/* ---------- refresh (scroll arrows + tab stops) ---------- */

		function refresh(p) {
			if (!p || p.el.hidden) {
				return;
			}
			updateScroll(p);
			updateStops(p);
			syncPin(p);
			updatePin(p);
		}

		/* ---------- pinned header ----------
		   The table grows with the page. While you scroll down, a copy of the header
		   row stays pinned to the top of the screen (below any fixed site menu). The
		   copy is built here, so the server-rendered HTML has a single header row. It
		   is aria-hidden and not focusable; the real header keeps keyboard focus. */

		function pinOK(p) {
			return !root.classList.contains('edcm--box') && !root.classList.contains('is-fs') && !!p.pin && !isCard(p);
		}

		function fixedBottomAt(y) {
			var out = 0;
			[0.2, 0.5, 0.8].forEach(function (f) {
				document.elementsFromPoint(Math.round(window.innerWidth * f), y).forEach(function (n) {
					if (root.contains(n) || n === document.body || n === document.documentElement) {
						return;
					}
					var pos = window.getComputedStyle(n).position;
					if (pos === 'fixed' || pos === 'sticky') {
						var r = n.getBoundingClientRect();
						if (r.top <= y && r.bottom > y && r.height < window.innerHeight * 0.4) {
							out = Math.max(out, r.bottom);
						}
					}
				});
			});
			return out;
		}

		var topCache = { at: 0, v: 0 };

		function stickyTop() {
			var fixed = root.getAttribute('data-sticky-top');
			if (fixed !== null && fixed !== '' && !isNaN(Number(fixed))) {
				return Number(fixed);
			}
			var now = Date.now();
			if (now - topCache.at < 150) {
				return topCache.v;
			}
			var top = 0;
			if (document.elementsFromPoint) {
				// Walk down through stacked fixed bars (e.g. the WordPress admin bar, then the site menu).
				var y = 2;
				for (var i = 0; i < 3; i++) {
					var b = fixedBottomAt(y);
					if (b <= y) {
						break;
					}
					top = b;
					y = Math.ceil(b) + 2;
				}
			}
			topCache.at = now;
			topCache.v = Math.ceil(top);
			return topCache.v;
		}

		function buildPin(p) {
			var head = p.table ? qs('thead', p.table) : null;
			if (p.pin || !head || !p.scroller) {
				return;
			}
			var pin = el('div', 'edcm__pin');
			pin.setAttribute('aria-hidden', 'true');
			var tbl = el('table', 'edcm__table edcm__table--pin');
			tbl.setAttribute('role', 'presentation');
			var copy = head.cloneNode(true);
			qsa('[id]', copy).forEach(function (n) {
				n.removeAttribute('id');
			});
			qsa('[role]', copy).forEach(function (n) {
				n.removeAttribute('role');
			});
			qsa('button', copy).forEach(function (b) {
				b.tabIndex = -1;
			});
			tbl.appendChild(copy);
			pin.appendChild(tbl);
			p.scroller.parentNode.insertBefore(pin, p.scroller);
			p.pin = pin;
			p.pinTable = tbl;
			p.pinHead = copy;
			p.pinH = 0;
			p.fixedMode = false;
			// Sideways wheel / shift+wheel over the pinned header scrolls the table.
			pin.addEventListener('wheel', function (e) {
				var dx = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : (e.shiftKey ? e.deltaY : 0);
				if (dx && p.scroller.scrollWidth > p.scroller.clientWidth) {
					p.scroller.scrollLeft += dx;
					e.preventDefault();
				}
			}, { passive: false });
		}

		/* Mirror column widths and hidden columns from the real header. */
		function syncPin(p) {
			if (!p.pin || p.el.hidden) {
				return;
			}
			if (!pinOK(p)) {
				p.pin.classList.remove('is-on', 'is-fixed');
				root.style.setProperty('--edcm-pinh', '0px');
				return;
			}
			var head = qs('thead', p.table);
			var h = head ? head.offsetHeight : 0;
			if (!h) {
				return;
			}
			p.pinH = h + 1;
			p.pin.style.height = p.pinH + 'px';
			p.pin.style.marginBottom = '-' + p.pinH + 'px';
			p.pinTable.style.width = p.table.getBoundingClientRect().width + 'px';
			var real = qsa('th', head);
			var copy = qsa('th', p.pinHead);
			real.forEach(function (th, i) {
				var c = copy[i];
				if (!c) {
					return;
				}
				var hid = th.hasAttribute('hidden');
				setHidden(c, hid);
				if (!hid) {
					c.style.width = th.getBoundingClientRect().width + 'px';
				}
			});
			p.pin.scrollLeft = p.scroller.scrollLeft;
			root.style.setProperty('--edcm-pinh', p.pinH + 'px');
		}

		/* Show the pinned copy once the real header has scrolled past the top. */
		function updatePin(p) {
			if (!p || !p.pin || p.el.hidden) {
				return;
			}
			var pin = p.pin;
			if (!pinOK(p) || !p.pinH) {
				pin.classList.remove('is-on', 'is-fixed');
				return;
			}
			var T = stickyTop();
			if (root.__edcmTop !== T) {
				root.__edcmTop = T;
				root.style.setProperty('--edcm-top', T + 'px');
			}
			var r = p.scroller.getBoundingClientRect();
			var h = p.pinH;
			var on = r.top < T - 1 && r.bottom > T;
			if (!on) {
				pin.classList.remove('is-on', 'is-fixed');
				pin.style.left = pin.style.top = pin.style.width = pin.style.transform = '';
				return;
			}
			pin.classList.add('is-on');
			if (!p.fixedMode && r.bottom > T + h + 8 && Math.abs(pin.getBoundingClientRect().top - T) > 3) {
				// position:sticky is being blocked by a theme wrapper (overflow, contain...): pin with fixed instead.
				p.fixedMode = true;
			}
			if (p.fixedMode) {
				pin.classList.add('is-fixed');
				pin.style.left = r.left + 'px';
				pin.style.width = r.width + 'px';
				pin.style.top = Math.min(T, r.bottom - h) + 'px';
				pin.style.transform = '';
			} else {
				pin.classList.remove('is-fixed');
				pin.style.left = pin.style.top = pin.style.width = '';
				// The negative margin that keeps the copy out of the layout also lets sticky ignore the
				// table's end, so slide it away with the last rows ourselves.
				var shift = Math.min(0, r.bottom - (T + h));
				pin.style.transform = shift ? 'translateY(' + shift + 'px)' : '';
			}
		}

		panels.forEach(buildPin);

		var pinTick = false;
		function onPageScroll() {
			if (!pinTick) {
				pinTick = true;
				raf(function () {
					pinTick = false;
					updatePin(active);
				});
			}
		}
		window.addEventListener('scroll', onPageScroll, { passive: true });
		window.addEventListener('resize', onPageScroll);
		if (window.ResizeObserver) {
			var pinRO = new ResizeObserver(function () {
				if (active) {
					syncPin(active);
					updatePin(active);
				}
			});
			panels.forEach(function (p) {
				if (p.table) {
					pinRO.observe(p.table);
				}
			});
		}

		/* ---------- wire up each panel ---------- */

		panels.forEach(function (p) {
			var search = qs('.edcm__search', p.el);
			var cat = qs('.edcm__cat', p.el);
			var netsel = qs('.edcm__netsel', p.el);
			var timer;

			if (search) {
				search.addEventListener('input', function () {
					clearTimeout(timer);
					timer = setTimeout(function () {
						p.q = search.value;
						applyFilters(p);
					}, 90);
				});
			}
			if (cat) {
				cat.addEventListener('change', function () {
					p.cat = cat.value;
					applyFilters(p);
				});
			}
			if (netsel) {
				netsel.addEventListener('change', function () {
					p.net = netsel.value;
					applyFilters(p);
				});
			}

			qsa('.edcm__collist input', p.el).forEach(function (cb) {
				cb.addEventListener('change', function () {
					p.hiddenCols[cb.getAttribute('data-net')] = !cb.checked;
					applyCols(p);
				});
			});
			qsa('[data-cols]', p.el).forEach(function (btn) {
				btn.addEventListener('click', function () {
					var mode = btn.getAttribute('data-cols');
					var top = (btn.getAttribute('data-top') || '').split(',');
					p.netIds.forEach(function (id) {
						p.hiddenCols[id] = mode === 'none' || (mode === 'top' && top.indexOf(id) === -1);
					});
					applyCols(p);
				});
			});

			var showAll = qs('.edcm__showall', p.el);
			if (showAll) {
				showAll.addEventListener('change', function () {
					root.setAttribute('data-only-supported', showAll.checked ? '0' : '1');
					qsa('.edcm__showall', root).forEach(function (c) {
						c.checked = showAll.checked;
					});
				});
			}

			qsa('[data-scroll]', p.el).forEach(function (btn) {
				btn.addEventListener('click', function () {
					var dir = parseInt(btn.getAttribute('data-scroll'), 10) || 1;
					p.scroller.scrollBy({ left: dir * p.scroller.clientWidth * 0.7, behavior: reduceMotion ? 'auto' : 'smooth' });
				});
			});

			if (p.scroller) {
				var ticking = false;
				p.scroller.addEventListener('scroll', function () {
					if (p.pin) {
						p.pin.scrollLeft = p.scroller.scrollLeft;
					}
					if (!ticking) {
						ticking = true;
						raf(function () {
							ticking = false;
							updateScroll(p);
						});
					}
				}, { passive: true });
				p.scroller.addEventListener('keydown', function (e) {
					onGridKey(p, e);
				});
				p.scroller.addEventListener('focusin', function (e) {
					var n = e.target.closest ? e.target.closest('.edcm__netbtn, .edcm__rowbtn, .edcm__cellbtn') : null;
					if (n && !isCard(p)) {
						if (p.cur && p.cur !== n) {
							p.cur.tabIndex = -1;
						}
						n.tabIndex = 0;
						p.cur = n;
					}
				});
			}

			qsa('[data-action]', p.el).forEach(function (btn) {
				btn.addEventListener('click', function () {
					if (btn.getAttribute('data-action') === 'csv') {
						exportCsv(p);
					} else if (btn.getAttribute('data-action') === 'fullscreen') {
						setFullscreen(!root.classList.contains('is-fs'));
					}
				});
			});
		});

		/* Close the column picker when clicking elsewhere or pressing Escape. */
		document.addEventListener('click', function (e) {
			qsa('.edcm__cols[open]', root).forEach(function (d) {
				if (!d.contains(e.target)) {
					d.removeAttribute('open');
				}
			});
		});
		root.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				var open = qsa('.edcm__cols[open]', root);
				if (open.length) {
					open.forEach(function (d) {
						d.removeAttribute('open');
						var s = qs('summary', d);
						if (s) {
							s.focus();
						}
					});
					e.stopPropagation();
				}
			}
		});

		/* ---------- CSV export ---------- */

		function csvCell(v) {
			v = String(v === undefined || v === null ? '' : v);
			if (/^[=+\-@\t\r]/.test(v)) {
				v = "'" + v;
			}
			return '"' + v.replace(/"/g, '""') + '"';
		}

		function exportCsv(p) {
			var g = visible(p);
			var corner = qs('.edcm__corner', p.table || p.el);
			var head = [corner ? corner.textContent : 'Name', 'Owner', 'Category'];
			g.cols.forEach(function (id) {
				head.push((netInfo[id] && netInfo[id].name) || id);
			});
			head.push(t('notes'));
			var lines = [head.map(csvCell).join(',')];
			g.rows.forEach(function (r) {
				var line = [r.getAttribute('data-name'), r.getAttribute('data-owner'), r.getAttribute('data-catlabel')];
				g.cols.forEach(function (id) {
					var l = qs('.edcm__lbl', r._c[id]);
					line.push(l ? l.textContent : codeOf(r, id));
				});
				line.push(r.getAttribute('data-notes'));
				lines.push(line.map(csvCell).join(','));
			});
			var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
			var name = t('csvName') + '-' + p.key + '.csv';
			if (window.navigator.msSaveBlob) {
				window.navigator.msSaveBlob(blob, name);
				return;
			}
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = name;
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			setTimeout(function () {
				URL.revokeObjectURL(url);
			}, 1500);
		}

		/* ---------- full screen + scroll lock ---------- */

		function syncLock() {
			var any = document.querySelector('.edcm.is-fs') || document.querySelector('.edcm__dialog[open]');
			document.body.classList.toggle('edcm-lock', !!any);
		}

		function setFullscreen(on) {
			root.classList.toggle('is-fs', on);
			qsa('[data-action="fullscreen"]', root).forEach(function (btn) {
				btn.setAttribute('aria-pressed', on ? 'true' : 'false');
				var span = qs('span', btn);
				if (span) {
					span.textContent = on ? t('exitFullscreen') : t('fullscreen');
				}
				var use = qs('use', btn);
				if (use) {
					use.setAttribute('href', on ? '#edcm-i-collapse' : '#edcm-i-expand');
				}
			});
			if (on) {
				root.scrollTop = 0;
			}
			syncLock();
			raf(function () {
				refresh(active);
			});
		}

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && root.classList.contains('is-fs') && !dialog.open) {
				setFullscreen(false);
			}
		});

		/* ---------- detail dialog ---------- */

		var opener = null;

		function pill(code, withLabel) {
			var s = el('span', 'edcm__pill' + (withLabel ? ' edcm__pill--label' : ''));
			s.setAttribute('data-code', code);
			s.appendChild(svg(ICONS[code] || ICONS.APP));
			s.appendChild(el('span', 'edcm__lbl', labels[code] || code));
			return s;
		}

		function linkButton(text, fn) {
			var b = el('button', null, text);
			b.type = 'button';
			b.addEventListener('click', fn);
			return b;
		}

		function dlgHead(title, sub) {
			var head = el('div', 'edcm__dlghead');
			var wrap = el('div', 'edcm__dlgtitles');
			var h = el('h3', 'edcm__dlgtitle', title);
			h.id = root.id + '-dlg-title';
			h.tabIndex = -1;
			wrap.appendChild(h);
			if (sub) {
				wrap.appendChild(el('p', 'edcm__dlgsub', sub));
			}
			var close = el('button', 'edcm__dlgclose');
			close.type = 'button';
			close.setAttribute('aria-label', t('close'));
			close.appendChild(svg('edcm-i-close'));
			close.addEventListener('click', closeDialog);
			head.appendChild(wrap);
			head.appendChild(close);
			return head;
		}

		function rowNotes(row) {
			var lines = [
				[t('notes'), row.getAttribute('data-notes')],
				[t('system'), row.getAttribute('data-system')],
				[t('oemApp'), row.getAttribute('data-oemapp')]
			].filter(function (l) {
				return l[1];
			});
			if (!lines.length) {
				return null;
			}
			var box = el('div', 'edcm__dlgnote');
			lines.forEach(function (l, i) {
				var p = el('p');
				if (i === lines.length - 1) {
					p.style.marginBottom = '0';
				}
				p.appendChild(el('strong', null, l[0] + ': '));
				p.appendChild(document.createTextNode(l[1]));
				box.appendChild(p);
			});
			return box;
		}

		function navButton(dir, enabled, fn) {
			var b = el('button', 'edcm__btn');
			b.type = 'button';
			b.disabled = !enabled;
			if (!enabled) {
				b.style.opacity = '0.4';
			}
			if (dir < 0) {
				b.appendChild(svg('edcm-i-left'));
				b.appendChild(document.createTextNode(' ' + t('previous')));
			} else {
				b.appendChild(document.createTextNode(t('next') + ' '));
				b.appendChild(svg('edcm-i-right'));
			}
			b.addEventListener('click', fn);
			return b;
		}

		function subline(row) {
			return [row.getAttribute('data-owner'), row.getAttribute('data-catlabel')].filter(Boolean).join(' · ');
		}

		function renderCell(spec) {
			var row = spec.row;
			var id = spec.net;
			var info = netInfo[id] || { name: id };
			dlg.appendChild(dlgHead(row.getAttribute('data-name'), subline(row)));
			var body = el('div', 'edcm__dlgbody');
			var ul = el('ul', 'edcm__dlist');
			var li = el('li', 'edcm__ditem edcm__ditem--cell');
			li.appendChild(pill(codeOf(row, id), true));
			var nm = el('div', 'edcm__dname');
			nm.appendChild(linkButton(info.name, function () {
				openDialog({ type: 'net', net: id });
			}));
			li.appendChild(nm);
			var tx = detailOf(row, id);
			if (tx) {
				li.appendChild(el('div', 'edcm__dtext', tx));
			}
			ul.appendChild(li);
			body.appendChild(ul);
			var notes = rowNotes(row);
			if (notes) {
				notes.style.marginTop = '14px';
				body.appendChild(notes);
			}
			dlg.appendChild(body);
			var foot = el('div', 'edcm__dlgfoot');
			var b1 = el('button', 'edcm__btn', t('allFor', { name: row.getAttribute('data-name') }));
			b1.type = 'button';
			b1.addEventListener('click', function () {
				openDialog({ type: 'row', panel: spec.panel, row: row });
			});
			var b2 = el('button', 'edcm__btn', t('aboutNet', { name: info.name }));
			b2.type = 'button';
			b2.addEventListener('click', function () {
				openDialog({ type: 'net', net: id });
			});
			foot.appendChild(b1);
			foot.appendChild(b2);
			dlg.appendChild(foot);
		}

		function renderRow(spec) {
			var row = spec.row;
			var p = spec.panel;
			dlg.appendChild(dlgHead(row.getAttribute('data-name'), subline(row)));
			var body = el('div', 'edcm__dlgbody');
			body.appendChild(el('p', null, t('integrated', { n: row.getAttribute('data-ok'), total: p.netIds.length })));
			var notes = rowNotes(row);
			if (notes) {
				body.appendChild(notes);
			}
			GROUPS.forEach(function (code) {
				var ids = p.netIds.filter(function (id) {
					return codeOf(row, id) === code;
				});
				if (!ids.length) {
					return;
				}
				body.appendChild(el('h4', 'edcm__dgroup', labels[code] + ' (' + ids.length + ')'));
				var ul = el('ul', 'edcm__dlist');
				ids.forEach(function (id) {
					var li = el('li', 'edcm__ditem');
					li.appendChild(pill(code, false));
					var nm = el('div', 'edcm__dname');
					nm.appendChild(linkButton((netInfo[id] || {}).name || id, function () {
						openDialog({ type: 'net', net: id });
					}));
					li.appendChild(nm);
					var tx = detailOf(row, id);
					if (tx) {
						li.appendChild(el('div', 'edcm__dtext', tx));
					}
					ul.appendChild(li);
				});
				body.appendChild(ul);
			});
			var none = p.netIds.filter(function (id) {
				return codeOf(row, id) === 'NO';
			});
			if (none.length) {
				body.appendChild(el('h4', 'edcm__dgroup', labels.NO + ' (' + none.length + ')'));
				body.appendChild(el('p', 'edcm__dtext', none.map(function (id) {
					return (netInfo[id] || {}).name || id;
				}).join(', ')));
			}
			dlg.appendChild(body);

			var rows = p.rows.filter(function (r) {
				return !r.hidden;
			});
			var i = rows.indexOf(row);
			var foot = el('div', 'edcm__dlgfoot');
			foot.appendChild(navButton(-1, i > 0, function () {
				openDialog({ type: 'row', panel: p, row: rows[i - 1] }, null, true);
			}));
			foot.appendChild(navButton(1, i > -1 && i < rows.length - 1, function () {
				openDialog({ type: 'row', panel: p, row: rows[i + 1] }, null, true);
			}));
			dlg.appendChild(foot);
		}

		function renderNet(spec) {
			var id = spec.net;
			var info = netInfo[id] || { name: id, type: '', about: '', url: '', sources: [] };
			dlg.appendChild(dlgHead(info.name, info.type));
			var body = el('div', 'edcm__dlgbody');
			if (info.about) {
				body.appendChild(el('p', null, info.about));
			}
			if (info.url) {
				var lp = el('p');
				var a = el('a', null, t('officialSite'));
				a.href = info.url;
				a.target = '_blank';
				a.rel = 'noopener noreferrer';
				lp.appendChild(a);
				body.appendChild(lp);
			}
			if (info.sources && info.sources.length) {
				body.appendChild(el('h4', 'edcm__dgroup', t('sources')));
				var sl = el('ul', 'edcm__dsrc');
				info.sources.forEach(function (s) {
					var li = el('li');
					var sa = el('a', null, s.label);
					sa.href = s.url;
					sa.target = '_blank';
					sa.rel = 'noopener noreferrer';
					li.appendChild(sa);
					sl.appendChild(li);
				});
				body.appendChild(sl);
			}
			body.appendChild(el('h4', 'edcm__dgroup', t('whoWorksWith', { name: info.name })));
			panels.forEach(function (p) {
				var any = false;
				GROUPS.forEach(function (code) {
					var rows = p.rows.filter(function (r) {
						return codeOf(r, id) === code;
					});
					if (!rows.length) {
						return;
					}
					any = true;
					body.appendChild(el('h4', 'edcm__dgroup', cap(p.plural) + ' – ' + labels[code] + ' (' + rows.length + ')'));
					var ul = el('ul', 'edcm__dlist');
					rows.forEach(function (r) {
						var li = el('li', 'edcm__ditem');
						li.appendChild(pill(code, false));
						var nm = el('div', 'edcm__dname');
						nm.appendChild(linkButton(r.getAttribute('data-name'), function () {
							openDialog({ type: 'row', panel: p, row: r });
						}));
						li.appendChild(nm);
						var tx = detailOf(r, id);
						if (tx) {
							li.appendChild(el('div', 'edcm__dtext', tx));
						}
						ul.appendChild(li);
					});
					body.appendChild(ul);
				});
				if (!any) {
					body.appendChild(el('h4', 'edcm__dgroup', cap(p.plural)));
					body.appendChild(el('p', 'edcm__dtext', t('nobody')));
				}
			});
			dlg.appendChild(body);

			var ids = active ? active.netIds.filter(function (n) {
				return !active.hiddenCols[n];
			}) : panels[0].netIds;
			var i = ids.indexOf(id);
			var foot = el('div', 'edcm__dlgfoot');
			foot.appendChild(navButton(-1, i > 0, function () {
				openDialog({ type: 'net', net: ids[i - 1] }, null, true);
			}));
			foot.appendChild(navButton(1, i > -1 && i < ids.length - 1, function () {
				openDialog({ type: 'net', net: ids[i + 1] }, null, true);
			}));
			dlg.appendChild(foot);
		}

		function openDialog(spec, from, keepFocusOnTitle) {
			if (from) {
				opener = from;
			}
			while (dlg.firstChild) {
				dlg.removeChild(dlg.firstChild);
			}
			if (spec.type === 'cell') {
				renderCell(spec);
			} else if (spec.type === 'row') {
				renderRow(spec);
			} else {
				renderNet(spec);
			}
			var body = qs('.edcm__dlgbody', dlg);
			if (body) {
				body.scrollTop = 0;
			}
			if (!dialog.open) {
				if (typeof dialog.showModal === 'function') {
					dialog.showModal();
				} else {
					dialog.setAttribute('open', '');
				}
			}
			syncLock();
			if (keepFocusOnTitle) {
				var title = qs('.edcm__dlgtitle', dlg);
				if (title) {
					title.focus();
				}
			}
		}

		function onClosed() {
			syncLock();
			if (opener && document.contains(opener) && !opener.closest('[hidden]')) {
				try {
					opener.focus({ preventScroll: true });
				} catch (e) {
					opener.focus();
				}
			}
			opener = null;
		}

		function closeDialog() {
			if (typeof dialog.close === 'function') {
				dialog.close();
			} else {
				dialog.removeAttribute('open');
				onClosed();
			}
		}

		dialog.addEventListener('close', onClosed);
		dialog.addEventListener('click', function (e) {
			if (e.target === dialog) {
				closeDialog();
			}
		});

		root.addEventListener('click', function (e) {
			if (e.target.closest('.edcm__dialog')) {
				return;
			}
			var nb = e.target.closest('.edcm__netbtn');
			if (nb && root.contains(nb)) {
				openDialog({ type: 'net', net: nb.getAttribute('data-net') }, nb);
				return;
			}
			var rb = e.target.closest('.edcm__rowbtn');
			if (rb && root.contains(rb)) {
				var row = rb.closest('.edcm__row');
				openDialog({ type: 'row', panel: panelOf(row), row: row }, rb);
				return;
			}
			var td = e.target.closest('td.edcm__cell');
			if (td && root.contains(td)) {
				var r = td.closest('.edcm__row');
				openDialog({ type: 'cell', panel: panelOf(r), row: r, net: td.getAttribute('data-net') }, qs('.edcm__cellbtn', td));
			}
		});

		/* ---------- resize handling ---------- */

		var rt;
		function onResize() {
			clearTimeout(rt);
			rt = setTimeout(function () {
				refresh(active);
			}, 80);
		}
		if (window.ResizeObserver && inner) {
			new ResizeObserver(onResize).observe(inner);
		} else {
			window.addEventListener('resize', onResize);
		}

		/* ---------- start ---------- */

		var initial = root.getAttribute('data-initial') || panels[0].key;
		var m = index === 0 && /^#edcm-([a-z0-9_-]+)$/.exec(window.location.hash || '');
		var fromHash = false;
		if (m) {
			panels.forEach(function (p) {
				if (p.key === m[1]) {
					initial = p.key;
					fromHash = true;
				}
			});
		}
		panels.forEach(function (p) {
			applyCols(p);
		});
		activate(initial, false);
		if (fromHash) {
			raf(function () {
				root.scrollIntoView({ block: 'start' });
			});
		}
		if (index === 0) {
			// Links elsewhere on the page (e.g. "see the car brands") can point at #edcm-brands.
			window.addEventListener('hashchange', function () {
				var hm = /^#edcm-([a-z0-9_-]+)$/.exec(window.location.hash || '');
				if (hm && panels.some(function (p) { return p.key === hm[1]; })) {
					activate(hm[1], false);
					root.scrollIntoView({ block: 'start' });
				}
			});
		}
	}

	function boot() {
		qsa('[data-edcm]').forEach(function (root, i) {
			if (!root.__edcm) {
				root.__edcm = true;
				init(root, i);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
