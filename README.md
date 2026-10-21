# לוג ניהול פרויקטים — אתר

WordPress מותאם, בלי בילדרים. שני חלקים:

| נתיב | מה זה |
|---|---|
| `wp-content/plugins/log-core` | מודל התוכן: פרויקטים, משרות, מרכז ידע, צוות, לקוחות; מדרגת היקף אוטומטית; `GET /wp-json/log/v1/map` ו-`/stats`; סכמה (Organization, JobPosting); Setup בלחיצה אחת; ייבוא מ-ArcGIS (כלים → ייבוא מ-ArcGIS). |
| `wp-content/themes/log` | תצוגה בלבד. תבניות לכל נתיב באפיון, שני הכפתורים הצמודים, גופנים עצמיים, Vite. |
| `tools/deploy.sh` | בילד + העלאה ל-uPress (lftp, סיסמה מה-Keychain `log-pm-ftp`). |
| `tools/dev.sh` | WordPress מקומי ב-Playground על :8881, עם התיקיות מחוברות חי. |
| `docs/` | האפיון (v6, ו-v5.1 הקודם), הצעת המחיר, מיפוי ArcGIS. |

## עבודה יומיומית

```bash
./tools/dev.sh            # מקומי: http://127.0.0.1:8881
cd wp-content/themes/log && npm run dev   # Vite עם HMR (הגדירו LOG_VITE_DEV ב-wp-config)
./tools/deploy.sh         # לשרת
```

אחרי פריסה ראשונה: wp-admin → תוספים → הפעלת LOG Core, עיצוב → הפעלת LOG, כלים → LOG Setup.

## החלטות

- **שדות:** ACF Pro. הקבוצות ב-`log-core/acf-json`, בגרסה. לא עורכים שדות ב-UI בלי לסנכרן חזרה.
- **מפה:** Mapbox GL v3. הטוקן ב"הגדרות לוג" ולא בקוד.
- **מדרגת היקף:** מחושבת בשמירה (`inc/scale.php`), לא ידנית.
- **פרסום:** פרויקט מופיע באתר ובמפה רק עם "מאושר לפרסום".
- **אינדוקס:** חסום עד ההשקה (הגדרות → קריאה).
