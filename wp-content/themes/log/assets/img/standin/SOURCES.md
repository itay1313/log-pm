# Stand-in photographs

Stock that fills the two galleries until LOG uploads its own pictures. `log_standins()` in `inc/helpers.php`
serves them only while the gallery fields are empty: the moment an editor adds a picture in wp-admin, the real
ones take over and none of these show.

All from Unsplash under the Unsplash License (free, commercial use, no credit required; not for resale as-is).
Squares are 640 px, the careers ones 1000 x 750. Page: `unsplash.com/photos/<id>`.

| File | Where | What | Unsplash id |
|---|---|---|---|
| `jlm-1.jpg` | About, "ירושלים" | Light rail on Jaffa Road | 6UJDY5wM9Ek |
| `jlm-2.jpg` | About, "ירושלים" | Stone apartment building with balconies | npd6E8EkxAA |
| `jlm-3.jpg` | About, "ירושלים" | Stone building, palms and the tram by the wall | ZKPl5OIvY7k |
| `tlv-1.jpg` | About, "תל אביב" | White Bauhaus curve over treetops | 7w4Zl66nlSY |
| `tlv-2.jpg` | About, "תל אביב" | Boulevard with palms, walkers and cyclists | VKR9gNMlJpE |
| `tlv-3.jpg` | About, "תל אביב" | The Ayalon and the towers at night | LJzggyttyMQ |
| `life-1.jpg` | About, "אירועים ויום עבודה" | Three people over a floor plan, from above | PZilVx5rWF4 |
| `life-2.jpg` | About, "אירועים ויום עבודה" | A group sketching on paper around a table | U57GdKkmCus |
| `life-3.jpg` | About, "אירועים ויום עבודה" | A man at a desk with a model | WUY0W2RSiBw |
| `careers-1.jpg` | Careers, "איך זה נראה" | Presenting at a pin-up board | z8DcWATjs1w |
| `careers-2.jpg` | Careers, "איך זה נראה" | A team around a floor plan, from above | Pcd-BfyCgOA |
| `careers-3.jpg` | Careers, "איך זה נראה" | Scale model of a neighbourhood | YYaMk1sDKq4 |
| `careers-4.jpg` | Careers, "איך זה נראה" | Two people at a table of material boards | AhirGl0nhww |
| `careers-5.jpg` | Careers, "איך זה נראה" | A city map, pencils and a hand marking it | qoAIlAmLJBU |

The openings of the control company and careers pages are not here: they use LOG's own photographs from
`uploads/log-brand/` (`aerial.jpg`, `team.jpg`), through `log_own_photo()`.
