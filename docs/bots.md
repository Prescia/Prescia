# Prescia Framework — Bot Blocklist

This file lists user-agent patterns that are blocked by Prescia's bot protection system (`CONS_BOTPROTECT`).

Source: [Project Honey Pot — Harvester User Agents](http://www.projecthoneypot.org/harvester_useragents.php?dt=30)

> **Note:** Bot protection can be disabled by enabling `EconomicMode`. See the [FAQ](faq.md) for details.

## Blocked Bots

| User-Agent Pattern | URL | Reason |
|---|---|---|
| `SeznamBot` | http://fulltext.sblog.cz/ | 10 hits/minute for hours |
| `MJ12bot` | http://www.majestic12.co.uk/bot.php | Repeated page hits |
| `Java/` | — | Not a browser or known bot |
| `MSIE8.0` | — | Fake MSIE user-agent |
| `DigExt` | — | Unknown bot |
| `Firefox/6.0 Google` | — | Fake Firefox user-agent |
