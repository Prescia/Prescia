new TWTR.Widget({
          version: 2,
          type: 'profile',
          rpp: 1,
          interval: 30000,
          width: 260,
          height: 177,
          theme: {
            shell: {
              background: 'none',
              color: '#B00B92'
            },
            tweets: {
              background: '#FFFFFF',
              color: '#666666',
              links: '#B00B92'
            }
          },
          features: {
            scrollbar: false,
            loop: false,
            live: false,
            behavior: 'all'
          }
        }).render().setUser('').start();