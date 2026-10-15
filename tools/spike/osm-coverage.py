import json, sys, urllib.request, urllib.parse, time
# ~1.2 km boxes around LOG plan locations (from ArcGIS) + two big-city references
sites = {
 "חדרה · לב העיר (19,800 יח\"ד)": (32.4354, 34.9217),
 "חולון": (32.0158, 34.7874),
 "אור יהודה": (32.0292, 34.8565),
 "נס ציונה": (31.9300, 34.7980),
 "בית שמש": (31.7470, 34.9880),
 "רחובות": (31.8940, 34.8090),
 "קריית גת": (31.6100, 34.7640),
 "באר שבע": (31.2520, 34.7915),
 "תל אביב · מרכז": (32.0700, 34.7820),
 "ירושלים · מרכז": (31.7810, 35.2190),
}
d = 0.0055  # ~0.6 km half-size
def q(lat, lng):
    b = f"{lat-d},{lng-d},{lat+d},{lng+d}"
    return f"""[out:json][timeout:60];
(way["building"]({b});)->.all;
(way["building"]["height"]({b}); way["building"]["building:levels"]({b});)->.h;
.all out count; .h out count;"""
for name,(lat,lng) in sites.items():
    data = urllib.parse.urlencode({"data": q(lat,lng)}).encode()
    for attempt in range(3):
        try:
            with urllib.request.urlopen(urllib.request.Request("https://overpass.kumi.systems/api/interpreter", data=data, headers={"User-Agent":"log-pm-spike/1"}), timeout=90) as r:
                j = json.load(r); break
        except Exception as e:
            err = e; time.sleep(5)
    else:
        print(f"{name}: ERROR {err}"); continue
    counts = [int(el["tags"]["ways"]) for el in j["elements"] if el["type"]=="count"]
    total, tagged = counts[0], counts[1]
    pct = 100*tagged/total if total else 0
    print(f"{name:32s} buildings={total:5d}  with height/levels={tagged:4d} ({pct:4.1f}%)")
    time.sleep(2)
