/**
 * Lynbro Danadresse Autofill — universal address-autocomplete engine.
 *
 * A single ARIA-combobox widget that attaches to many WordPress plugins through
 * an ADAPTER REGISTRY. Each adapter knows how to find address field(s) for one
 * plugin and how a chosen address maps onto its sibling fields (street line,
 * postcode, city). The dropdown lives in <body> so React-managed DOM (the
 * WooCommerce Checkout Block) is never mutated around the input, and values are
 * written through the native setter so framework state stays in sync.
 *
 * Reverse-engineered field structures per plugin are documented inside each
 * adapter below. No dependencies. Config is injected via wp_localize_script as
 * lynbroDanadresseCfg.
 */
(function () {
  "use strict";

  var cfg = window.lynbroDanadresseCfg || {};
  var MIN = parseInt(cfg.minChars, 10) || 2;
  var DELAY = 150;
  var BOUND = "lynbroDanadresseBound";
  var ENABLED = cfg.integrations || {}; // { adapterId: true/false }

  /* ================================================================== *
   * ADAPTER REGISTRY — reverse-engineered per plugin.
   *
   * Each adapter: { id, test(), groups() -> [ {input, map} ] }
   *   input : the street/address text input to attach autocomplete to.
   *   map   : { postcode, city, country, street } of sibling elements or null.
   *           When map has postcode/city, the address is split across fields;
   *           otherwise the full address string is written into `input`.
   * ================================================================== */

  function $(sel, root) {
    try {
      return (root || document).querySelector(sel);
    } catch (e) {
      return null;
    }
  }
  function $all(sel, root) {
    try {
      return Array.prototype.slice.call(
        (root || document).querySelectorAll(sel),
      );
    } catch (e) {
      return [];
    }
  }

  var ADAPTERS = [
    /* WooCommerce classic checkout + My Account (shortcode-based). */
    {
      id: "woocommerce",
      test: function () {
        return cfg.woo;
      },
      groups: function () {
        return [
          {
            input: "#billing_address_1",
            postcode: "#billing_postcode",
            city: "#billing_city",
            country: "#billing_country",
          },
          {
            input: "#shipping_address_1",
            postcode: "#shipping_postcode",
            city: "#shipping_city",
            country: "#shipping_country",
          },
        ]
          .map(resolveGroup)
          .filter(Boolean);
      },
    },

    /* WooCommerce Cart/Checkout Block (React). IDs use hyphens, not underscores. */
    {
      id: "woocommerce_blocks",
      test: function () {
        return cfg.woo;
      },
      groups: function () {
        return [
          {
            input: "#billing-address_1",
            postcode: "#billing-postcode",
            city: "#billing-city",
            country: "#billing-country",
          },
          {
            input: "#shipping-address_1",
            postcode: "#shipping-postcode",
            city: "#shipping-city",
            country: "#shipping-country",
          },
        ]
          .map(resolveGroup)
          .filter(Boolean);
      },
    },

    /*
     * WPForms (Lite + Pro). The Address field renders sub-inputs with stable
     * classes inside a `.wpforms-field-address` wrapper:
     *   .wpforms-field-address-address1 (street)  .wpforms-field-address-address2
     *   .wpforms-field-address-city  .wpforms-field-address-state
     *   .wpforms-field-address-postal (postcode)  .wpforms-field-address-country
     */
    {
      id: "wpforms",
      test: function () {
        return !!$(".wpforms-field-address");
      },
      groups: function () {
        return $all(".wpforms-field-address")
          .map(function (wrap) {
            var input = $(".wpforms-field-address-address1", wrap);
            if (!input) {
              return null;
            }
            return {
              input: input,
              postcode: $(".wpforms-field-address-postal", wrap),
              city: $(".wpforms-field-address-city", wrap),
              country: $(".wpforms-field-address-country", wrap),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Gravity Forms. The Address field wraps sub-inputs in
     * `.ginput_container_address`; each sub-input id ends in an index:
     *   _1 street, _2 line2, _3 city, _4 state, _5 zip, _6 country.
     */
    {
      id: "gravityforms",
      test: function () {
        return !!$(".ginput_container_address");
      },
      groups: function () {
        return $all(".ginput_container_address")
          .map(function (wrap) {
            var input = $('[id$="_1"]', wrap);
            if (!input || input.tagName !== "INPUT") {
              return null;
            }
            return {
              input: input,
              postcode: $('[id$="_5"]', wrap),
              city: $('[id$="_3"]', wrap),
              country: $('select[id$="_6"]', wrap),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Fluent Forms. The Address field emits inputs named
     *   <name>[address_line_1], <name>[city], <name>[zip], <name>[country]
     * grouped in a `.ff-el-group` (or `.ff-address-group`) container.
     */
    {
      id: "fluentform",
      test: function () {
        return !!$('input[name$="[address_line_1]"]');
      },
      groups: function () {
        return $all('input[name$="[address_line_1]"]')
          .map(function (input) {
            var prefix = input.name.slice(0, -"[address_line_1]".length);
            var q = function (suffix) {
              return $('[name="' + prefix + suffix + '"]');
            };
            return {
              input: input,
              postcode: q("[zip]"),
              city: q("[city]"),
              country: q("[country]"),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Formidable Forms. The Address field emits sub-inputs whose id ends in
     *   -line1, -line2, -city, -state, -zip, -country.
     */
    {
      id: "formidable",
      test: function () {
        return !!$('input[id$="-line1"]');
      },
      groups: function () {
        return $all('input[id$="-line1"]')
          .map(function (input) {
            var base = input.id.slice(0, -"-line1".length);
            var byId = function (suffix) {
              return document.getElementById(base + suffix);
            };
            return {
              input: input,
              postcode: byId("-zip"),
              city: byId("-city"),
              country: byId("-country"),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Easy Digital Downloads checkout. Billing fields:
     *   #edd-card-address (street), #edd-card-city, #edd-card-zip, #billing_country.
     */
    {
      id: "edd",
      test: function () {
        return !!$("#edd-card-address");
      },
      groups: function () {
        var g = resolveGroup({
          input: "#edd-card-address",
          postcode: "#edd-card-zip",
          city: "#edd-card-city",
          country: "#billing_country",
        });
        return g ? [g] : [];
      },
    },

    /*
     * GiveWP donation forms (billing details). Give forked EDD's field names:
     *   input[name="card_address"] (street), card_city, card_zip, card_state,
     *   billing_country. Scoped to a .give-form so it never clashes with EDD.
     */
    {
      id: "givewp",
      test: function () {
        return !!$('.give-form input[name="card_address"]');
      },
      groups: function () {
        return $all('.give-form input[name="card_address"]')
          .map(function (input) {
            var form = input.closest(".give-form") || document;
            return {
              input: input,
              postcode: $('input[name="card_zip"]', form),
              city: $('input[name="card_city"]', form),
              country: $('select[name="billing_country"]', form),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Forminator address field. Sub-inputs are name-bracketed:
     *   <slug>[street_address], <slug>[city], <slug>[zip], <slug>[state],
     *   <slug>[country]; also carry classes .forminator-address--street_address
     *   etc. Key on the street sub-input and derive the siblings by prefix.
     */
    {
      id: "forminator",
      test: function () {
        return !!$(
          'input[name$="[street_address]"], .forminator-address--street_address',
        );
      },
      groups: function () {
        var streets = $all(
          'input[name$="[street_address]"], input.forminator-address--street_address',
        );
        return streets
          .map(function (input) {
            if (input.name && /\[street_address\]$/.test(input.name)) {
              var prefix = input.name.slice(0, -"[street_address]".length);
              var q = function (suffix) {
                return $('[name="' + prefix + suffix + '"]');
              };
              return {
                input: input,
                postcode: q("[zip]"),
                city: q("[city]"),
                country: q("[country]"),
              };
            }
            // Class-based fallback within the address wrapper.
            var wrap = input.closest(".forminator-address") || document;
            return {
              input: input,
              postcode: $(".forminator-address--zip", wrap),
              city: $(".forminator-address--city", wrap),
              country: $(".forminator-address--country", wrap),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Everest Forms address field. Sub-inputs are name-bracketed:
     *   ...[address1], ...[city], ...[postal], ...[state], ...[country].
     */
    {
      id: "everestforms",
      test: function () {
        return !!$(
          '.everest-forms input[name$="[address1]"], input[name$="[address_1]"]',
        );
      },
      groups: function () {
        return $all(
          '.everest-forms input[name$="[address1]"], .everest-forms input[name$="[address_1]"]',
        )
          .map(function (input) {
            var m = input.name.match(/^(.*)\[address_?1\]$/);
            if (!m) {
              return null;
            }
            var prefix = m[1];
            var q = function (suffix) {
              return $('[name="' + prefix + suffix + '"]');
            };
            return {
              input: input,
              postcode: q("[postal]") || q("[zip]"),
              city: q("[city]"),
              country: q("[country]"),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Paid Memberships Pro checkout (billing address). Stable field ids:
     *   #baddress1, #baddress2, #bcity, #bstate, #bzipcode, #bcountry.
     */
    {
      id: "pmpro",
      test: function () {
        return !!$("#baddress1");
      },
      groups: function () {
        var g = resolveGroup({
          input: "#baddress1",
          postcode: "#bzipcode",
          city: "#bcity",
          country: "#bcountry",
        });
        return g ? [g] : [];
      },
    },

    /*
     * Ultimate Member profile / register forms. The address is a single field
     *   .um-form input[name="address"]; if the form also has city / postcode
     *   fields (name="city" / "zip_code" / "postal_code"), fill them too.
     */
    {
      id: "ultimatemember",
      test: function () {
        return !!$('.um-form input[name="address"]');
      },
      groups: function () {
        return $all('.um-form input[name="address"]')
          .map(function (input) {
            var form = input.closest(".um-form") || document;
            return {
              input: input,
              postcode:
                $('input[name="zip_code"]', form) ||
                $('input[name="postal_code"]', form) ||
                $('input[name="postcode"]', form),
              city: $('input[name="city"]', form),
              country: $('select[name="country"]', form),
            };
          })
          .filter(Boolean);
      },
    },

    /*
     * Generic single-field: any input the site owner targeted through the
     * settings (CSS selectors), plus [data-lynbro-danadresse] / .lynbro-danadresse-field
     * inputs, plus — when auto-detect is on — inputs that clearly hold an
     * address by name/id/placeholder/label. The full address string is
     * written into the input; optional data-* attributes split it out.
     */
    {
      id: "generic",
      test: function () {
        return true;
      },
      groups: function () {
        var seen = [];
        var out = [];
        var add = function (node) {
          if (node && node.tagName === "INPUT" && seen.indexOf(node) === -1) {
            seen.push(node);
            out.push({ input: node, dataAttr: true });
          }
        };

        (cfg.selectors || []).forEach(function (sel) {
          $all(sel).forEach(add);
        });
        $all(
          "input[data-lynbro-danadresse], input.lynbro-danadresse-field, input[data-danadresse]",
        ).forEach(add);

        if (cfg.autoDetect) {
          $all('input[type="text"], input:not([type])').forEach(
            function (node) {
              if (looksLikeAddress(node)) {
                add(node);
              }
            },
          );
        }
        return out;
      },
    },
  ];

  /** Resolve a selector-based group descriptor into live elements. */
  function resolveGroup(desc) {
    var input = typeof desc.input === "string" ? $(desc.input) : desc.input;
    if (!input) {
      return null;
    }
    return {
      input: input,
      postcode:
        typeof desc.postcode === "string" ? $(desc.postcode) : desc.postcode,
      city: typeof desc.city === "string" ? $(desc.city) : desc.city,
      country:
        typeof desc.country === "string" ? $(desc.country) : desc.country,
      dataAttr: desc.dataAttr || false,
    };
  }

  /** Heuristic: does this input clearly hold a street address? */
  function looksLikeAddress(node) {
    var hay = [
      node.name,
      node.id,
      node.placeholder,
      node.getAttribute("aria-label"),
    ]
      .join(" ")
      .toLowerCase();
    if (
      /\b(post|zip|city|by|land|country|state|phone|email|navn|name)\b/.test(
        hay,
      )
    ) {
      return false;
    }
    if (/(address|adresse|vejnavn|gade|street|adr)/.test(hay)) {
      return true;
    }
    // Label text.
    var id = node.id;
    if (id) {
      var lbl = $('label[for="' + id + '"]');
      if (
        lbl &&
        /(address|adresse|vejnavn|gade|street)/i.test(lbl.textContent || "")
      ) {
        return true;
      }
    }
    return false;
  }

  /* ------------------------------------------------------------------ *
   * Utilities.
   * ------------------------------------------------------------------ */

  function debounce(fn, wait) {
    var t;
    return function () {
      var args = arguments,
        self = this;
      clearTimeout(t);
      t = setTimeout(function () {
        fn.apply(self, args);
      }, wait);
    };
  }

  /** Write a value framework-safely and fire the events listeners expect. */
  function setValue(el, value) {
    if (!el) {
      return;
    }
    var proto = Object.getPrototypeOf(el);
    var desc = Object.getOwnPropertyDescriptor(proto, "value");
    if (desc && desc.set) {
      desc.set.call(el, value);
    } else {
      el.value = value;
    }
    el.dispatchEvent(new Event("input", { bubbles: true }));
    el.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function countryAllows(group, input) {
    if (!cfg.dkOnly) {
      return true;
    }
    var sel = group && group.country ? group.country : null;
    if (!sel) {
      var attr =
        input.getAttribute("data-lynbro-danadresse-country") ||
        input.getAttribute("data-danadresse-country");
      sel = attr ? $(attr) : null;
    }
    if (!sel || typeof sel.value === "undefined" || sel.value === "") {
      return true;
    }
    return String(sel.value).toUpperCase() === "DK";
  }

  /* ------------------------------------------------------------------ *
   * Dropdown singleton.
   * ------------------------------------------------------------------ */

  var panel = null,
    live = null,
    activeInput = null,
    items = [],
    highlighted = -1,
    pendingDrill = null;

  function ensurePanel() {
    if (panel) {
      return panel;
    }
    panel = document.createElement("ul");
    panel.className = "lynbro-danadresse-panel danadresse-panel";
    panel.id = "lynbro-danadresse-listbox";
    panel.setAttribute("role", "listbox");
    panel.hidden = true;
    document.body.appendChild(panel);

    live = document.createElement("div");
    live.className = "lynbro-danadresse-sr-only danadresse-sr-only";
    live.setAttribute("aria-live", "polite");
    document.body.appendChild(live);

    panel.addEventListener("mousedown", function (e) {
      var li = e.target.closest("li[data-idx]");
      if (li) {
        e.preventDefault();
        choose(parseInt(li.getAttribute("data-idx"), 10));
      }
    });
    document.addEventListener("click", function (e) {
      if (
        activeInput &&
        e.target !== activeInput &&
        !panel.contains(e.target)
      ) {
        close();
      }
    });
    window.addEventListener("resize", position);
    window.addEventListener("scroll", position, true);
    return panel;
  }

  function position() {
    if (!activeInput || !panel || panel.hidden) {
      return;
    }
    var r = activeInput.getBoundingClientRect();
    panel.style.left = r.left + window.scrollX + "px";
    panel.style.top = r.bottom + window.scrollY + 2 + "px";
    panel.style.minWidth = r.width + "px";
  }

  function close() {
    if (!panel) {
      return;
    }
    panel.hidden = true;
    panel.innerHTML = "";
    highlighted = -1;
    items = [];
    if (activeInput) {
      activeInput.setAttribute("aria-expanded", "false");
      activeInput.removeAttribute("aria-activedescendant");
    }
  }

  function render(list) {
    ensurePanel();
    panel.innerHTML = "";
    items = list;
    highlighted = -1;
    if (!list.length) {
      close();
      return;
    }

    list.forEach(function (item, i) {
      var li = document.createElement("li");
      li.id = "lynbro-danadresse-opt-" + i;
      li.setAttribute("role", "option");
      li.setAttribute("data-idx", String(i));
      li.setAttribute("aria-selected", "false");
      li.textContent = item.forslagstekst || item.tekst;
      if (
        item.type &&
        item.type !== "adresse" &&
        item.type !== "adgangsadresse"
      ) {
        li.className = "danadresse-more";
      }
      panel.appendChild(li);
    });

    panel.hidden = false;
    activeInput.setAttribute("aria-expanded", "true");
    position();
    if (live && cfg.labels && cfg.labels.results) {
      live.textContent = cfg.labels.results.replace("%d", String(list.length));
    }
  }

  function highlight(idx) {
    if (!items.length) {
      return;
    }
    var options = panel.querySelectorAll("li[data-idx]");
    if (highlighted >= 0 && options[highlighted]) {
      options[highlighted].classList.remove("is-active");
      options[highlighted].setAttribute("aria-selected", "false");
    }
    highlighted = (idx + items.length) % items.length;
    var el = options[highlighted];
    el.classList.add("is-active");
    el.setAttribute("aria-selected", "true");
    el.scrollIntoView({ block: "nearest" });
    activeInput.setAttribute("aria-activedescendant", el.id);
  }

  /* ------------------------------------------------------------------ *
   * Data flow.
   * ------------------------------------------------------------------ */

  var controller = null;

  // A page cache can hand out a nonce that has already expired. The endpoint
  // then answers 403 / -1; fetch a fresh nonce once and repeat the lookup.
  function refreshNonce() {
    var body = new URLSearchParams();
    body.set("action", "lynbro_danadresse_nonce");
    return fetch(cfg.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      cache: "no-store",
      headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
      body: body.toString(),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (json && json.success && json.data && json.data.nonce) {
          cfg.nonce = json.data.nonce;
          return true;
        }
        return false;
      });
  }

  function fetchSuggestions(q, caretpos, retried) {
    if (controller) {
      controller.abort();
    }
    controller = "AbortController" in window ? new AbortController() : null;

    var body = new URLSearchParams();
    body.set("action", "lynbro_danadresse_autocomplete");
    body.set("nonce", cfg.nonce || "");
    body.set("q", q);
    if (caretpos >= 0) {
      body.set("caretpos", String(caretpos));
    }

    return fetch(cfg.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
      },
      body: body.toString(),
      signal: controller ? controller.signal : undefined,
    })
      .then(function (res) {
        if (res.status === 403 && !retried) {
          return refreshNonce().then(function (ok) {
            return ok ? fetchSuggestions(q, caretpos, true) : [];
          });
        }
        return res.json().then(function (json) {
          return json && json.success && json.data && json.data.items
            ? json.data.items
            : [];
        });
      });
  }

  var query = debounce(function (input) {
    var q = input.value;
    if (q.trim().length < MIN || !countryAllows(input._lynbroGroup, input)) {
      close();
      return;
    }
    fetchSuggestions(q, input.selectionStart || q.length)
      .then(function (list) {
        // Resolve a pending drill: the user picked an access address and we
        // re-queried for its units. No unit came back → that access address was
        // the final answer, so fill it instead of showing a dead-end list.
        if (pendingDrill && pendingDrill.input === input) {
          var drilled = pendingDrill;
          pendingDrill = null;
          if (!list.some(isFinal)) {
            fill(drilled.input, drilled.item);
            close();
            return;
          }
        }
        if (input === activeInput && document.activeElement === input) {
          render(list);
        }
      })
      .catch(function () {
        /* aborted or offline — stay quiet */
      });
  }, DELAY);

  /** Street line composed from the address payload (never raw tekst, which can
   *  contain empty segments like "Amagertorv 1, , 1160 København K"). */
  function streetLine(d) {
    var line = (d.vejnavn || "") + (d.husnr ? " " + d.husnr : "");
    if (d.etage || d["dør"]) {
      line +=
        "," +
        (d.etage ? " " + d.etage + "." : "") +
        (d["dør"] ? " " + d["dør"] : "");
    }
    return line.trim();
  }

  /** Full one-line address, skipping empty parts. */
  function fullLine(d, fallback) {
    var street = streetLine(d);
    var tail = [d.postnr, d.postnrnavn].filter(Boolean).join(" ");
    if (!street) {
      return fallback || "";
    }
    return tail ? street + ", " + tail : street;
  }

  /**
   * Is this suggestion final, or a step on the way?
   *
   * Decided by payload completeness, never by `type` — autocomplete is stepwise
   * and legitimately returns `type:"adgangsadresse"` items while the query has
   * not yet narrowed to one unit, exactly as DAWA does. Keying on the type
   * would treat a perfectly good address as an intermediate step.
   *
   * When the site wants floor/door (tier 'adresse'), only a real unit address
   * counts as final; an access address is drilled into first — and filled
   * anyway if the building turns out to have no units (see drill handling in
   * choose()).
   */
  function isFinal(item) {
    var d = item.data || {};
    if (cfg.tier === "adgangsadresse") {
      return Boolean(d.vejnavn && d.husnr && d.postnr);
    }
    return Boolean(d.etage || d["dør"] || d.adgangsadresseid);
  }

  /** Can this item fill a form on its own (street + postcode present)? */
  function isFillable(item) {
    var d = item.data || {};
    return Boolean(d.vejnavn && d.husnr && d.postnr);
  }

  function choose(idx) {
    var item = items[idx];
    if (!item || !activeInput) {
      return;
    }
    var input = activeInput;

    if (!isFinal(item)) {
      // Intermediate step (street name, or an access address whose units we
      // have not seen yet): adopt the suggestion text and query again from the
      // caret position. Always `forslagstekst` — `tekst` carries an empty
      // segment ("Amagertorv 1, , 1160 København K") by DAWA convention.
      var partial = item.forslagstekst || item.tekst || "";
      setValue(input, partial);
      input.focus();
      try {
        var pos = item.caretpos || partial.length;
        input.setSelectionRange(pos, pos);
      } catch (e) {
        /* type mismatch */
      }
      // If the drill turns up no unit-level address, this access address was
      // the answer after all — fill it rather than leaving the user stuck.
      pendingDrill = isFillable(item) ? { item: item, input: input } : null;
      close();
      query(input);
      return;
    }
    pendingDrill = null;
    fill(input, item);
    close();
  }

  function fill(input, item) {
    var d = item.data || {};
    var group = input._lynbroGroup;

    if (group && group.postcode) {
      setValue(input, streetLine(d) || item.forslagstekst || item.tekst);
      setValue(group.postcode, d.postnr || "");
      setValue(group.city, d.postnrnavn || "");
    } else {
      setValue(input, fullLine(d, item.forslagstekst || item.tekst));
      var targets = {
        vejnavn: d.vejnavn || "",
        husnr: d.husnr || "",
        postnr: d.postnr || "",
        by: d.postnrnavn || "",
      };
      Object.keys(targets).forEach(function (key) {
        var sel =
          input.getAttribute("data-lynbro-danadresse-" + key) ||
          input.getAttribute("data-danadresse-" + key);
        if (sel) {
          setValue($(sel), targets[key]);
        }
      });
    }
    input.dispatchEvent(
      new CustomEvent("lynbro-danadresse:selected", {
        bubbles: true,
        detail: item,
      }),
    );
  }

  /* ------------------------------------------------------------------ *
   * Attachment.
   * ------------------------------------------------------------------ */

  function bind(input, group) {
    if (!input || input.dataset[BOUND]) {
      return;
    }
    input.dataset[BOUND] = "1";
    input._lynbroGroup = group || null;

    input.setAttribute("role", "combobox");
    input.setAttribute("aria-autocomplete", "list");
    input.setAttribute("aria-expanded", "false");
    input.setAttribute("aria-controls", "lynbro-danadresse-listbox");
    if (input.autocomplete !== "off") {
      input.autocomplete = "off";
    }

    input.addEventListener("focus", function () {
      activeInput = input;
    });
    input.addEventListener("input", function (e) {
      if (!e.isTrusted) {
        return;
      }
      activeInput = input;
      ensurePanel();
      query(input);
    });
    input.addEventListener("keydown", function (e) {
      if (!panel || panel.hidden || input !== activeInput) {
        return;
      }
      switch (e.key) {
        case "ArrowDown":
          e.preventDefault();
          highlight(highlighted + 1);
          break;
        case "ArrowUp":
          e.preventDefault();
          highlight(highlighted - 1);
          break;
        case "Enter":
          if (highlighted >= 0) {
            e.preventDefault();
            choose(highlighted);
          }
          break;
        case "Tab":
          if (highlighted >= 0) {
            choose(highlighted);
          } else {
            close();
          }
          break;
        case "Escape":
          e.preventDefault();
          close();
          break;
      }
    });
    input.addEventListener("blur", function () {
      setTimeout(function () {
        if (document.activeElement !== input) {
          close();
        }
      }, 150);
    });
  }

  function attachAll() {
    ADAPTERS.forEach(function (adapter) {
      // 'generic' always runs; others honour the enabled-integrations map
      // (default on when unspecified) and their own presence test.
      if (adapter.id !== "generic" && ENABLED[adapter.id] === false) {
        return;
      }
      if (!adapter.test()) {
        return;
      }
      adapter.groups().forEach(function (group) {
        bind(group.input, group);
      });
    });
  }

  /* ------------------------------------------------------------------ *
   * Boot + keep up with dynamic DOM (Checkout Block, AJAX/multi-step forms).
   * ------------------------------------------------------------------ */

  function boot() {
    attachAll();
    var observer = new MutationObserver(debounce(attachAll, 250));
    observer.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
