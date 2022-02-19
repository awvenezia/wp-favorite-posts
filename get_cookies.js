// Implementing the Client-Side logic for cookies based fav posts fue to server-side caching.
const WP_FAV_COOKIE = 'wp-favorite-posts';
(($)=> {
    let lastCookie = document.cookie;
    // rename document.cookie to document._cookie, and redefine document.cookie
    const expando = '_cookie';
    let nativeCookieDesc = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie');
    Object.defineProperty(Document.prototype, expando, nativeCookieDesc);
    Object.defineProperty(Document.prototype, 'cookie', {
      enumerable: true,
      configurable: true,
      get() {
        return this[expando];
      },
      set(value) {
        this[expando] = value;
        // check cookie change
        let cookie = this[expando];
        if (cookie !== lastCookie) {
          try {
            // dispatch cookie-change messages to other same-origin tabs/frames
            let detail = {oldValue: lastCookie, newValue: cookie};
            this.dispatchEvent(new CustomEvent('cookiechange', {
              detail: detail
            }));
            channel.postMessage(detail);
          } finally {
            lastCookie = cookie;
          }
        }
      }
    });
    // listen cookie-change messages from other same-origin tabs/frames
    const channel = new BroadcastChannel('cookie-channel');
    channel.onmessage = (e)=> {
      lastCookie = e.data.newValue;
      console.log("Cookie change observerd");
      document.dispatchEvent(new CustomEvent('cookiechange', {
        detail: e.data
      }));
    };

    document.addEventListener('cookiechange', ({detail: {oldValue, newValue}})=> {
        console.log(`Cookie changed from "${oldValue}" to "${newValue}"`);
        if(newValue.includes(WP_FAV_COOKIE)) {
            updateCookie(document.cookie);
        }
    });

    window.updateCookie = (cookieString) => {
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : fetchCookies.ajaxurl,
            data : {action: "fetch_cookies", cookie_string : cookieString},
            success: function(response) {
               if(response.type == "success") {
                  console.log("Updated Fav Posts")
               }
               else {
                  console.log("SWW on updating Fav posts");
               }
            }
         });
    }

  })(jQuery);
