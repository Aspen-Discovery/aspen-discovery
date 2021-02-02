## Subject Facets

Aspen Discovery pulls subject facets from the following fields: "600", "610", "611", "630", "648", "650", "651", "655", "690". It handles topic, era, genre, geographic region, lc subject, bisac subject, and subjects from these fields.

The following logic is followed for the facet organization of those fields.

### Within the 600 field:
If the subfield is >= 'a' AND <= 'h' OR >= 'j' AND <= 'v' OR >= 'x' AND <= 'z' then those subfields are combined into the full subject. This will happen for each instance of the 600.
If the subfield is 'a' OR 'x' then the individual subfield is a topic facet.
If the subfield is 'v' then the individual subfield is a genre facet.
If the subfield is 'z' then the individual subfield is a geographic facet.
If the subfield is 'd' then the individual subfield is an era facet.

### Within the 610 field:
If the subfield is >= 'a' AND <= 'h' OR >= 'j' AND <= 'v' OR >= 'x' AND <= 'z' then those subfields are combined into the full subject. This will happen for each instance of the 610.
If the subfield is 'x' then the individual subfield is a topic facet.
If the subfield is 'v' then the individual subfield is a genre facet.
If the subfield is 'z' then the individual subfield is a geographic facet.
If the subfield is 'y' then the individual subfield is an era facet.

### Within the 611 field:
If the subfield is 'a' OR >= 'c' AND <= 'h' OR >= 'k' AND <= 'l' OR the subfield is 'n' OR 'p' OR the subfield is 's' OR the subfield is >= 'p' AND <= 'v' OR >= 'x' AND <= 'z' then those subfields are combined into the full subject. This will happen for each instance of the 611.
If the subfield is 'x' then the individual subfield is a topic facet.
If the subfield is 'v' then the individual subfield is a genre facet.
If the subfield is 'z' then the individual subfield a geographic facet.
If the subfield is 'y' then the individual subfield is an era facet.

### Within the 630 field:
If the subfield is 'a' OR 'b' OR >= 'f' AND <= 'h' OR >= 'k' AND <= 'p' OR >= 'r' AND <= 't' OR >= 'v' OR >= 'x' AND <= 'z' then those subfields are combined into the full subject. This will happen for each instance of the 630.
If the subfield is 'x' then the individual subfield is a topic facet.
If the subfield is 'v' then the individual subfield is a genre facet.
If the subfield is 'z' then the individual subfield is a geographic facet.
If the subfield is 'y' then the individual subfield is an era facet.

### Within the 648 field:
If the subfield is 'x' then the individual subfield is a topic facet.
If the subfield is 'v' then the individual subfield is a genre facet.
If the subfield is 'z' then the individual subfield is a geographic facet.
If the subfield is 'a' OR 'y'then the individual subfield is an era facet.

### Within the 650 field:
Aspen will treat 650 fields where subfield 2 holds "bisacsh" OR "bisacmt" OR "bisacrt" as BISAC Subjects.
Aspen will treat 650 fields with a 2nd indicator of '0' OR '1' as an LC Subject (if a subject is BISAC, it cannot also be an LC Subject).
If the subfield is >= 'a' AND <= 'e' OR >= 'v' OR >= 'x' AND <= 'z' then those subfields are combined into the full subject.
If the subfield is 'a' OR == 'x' then the individual subfield is a topic facet.
If the subfield is 'v' then the individual subfield is a genre facet.
If the subfield is 'z' then the individual subfield is a geographic facet.
If the subfield is 'y' then the individual subfield is is an era facet.

### Within the 651 field:
If the subfield is >= 'a' AND <= 'e' OR >= 'v' OR >= 'x' AND <= 'z' then those subfields are combined into the full subject. This will happen for each instance of the 651.
If the subfield is 'x' then the individual subfield is a topic and geographic facet.
If the subfield is 'v' then the individual subfield is a genre and geographic facet.
If the subfield is 'a' OR 'z' then the individual subfield is a geographic facet.
If the subfield is 'y' then the individual subfield is an era and geographic facet.

### Within the 655 field:
If the subfield is >= 'a' AND <= 'c' OR >= 'v' OR >= 'x' & <= 'z' then those subfields are combined into the full subject. This will happen for each instance of the 655.
If the subfield is 'x' then the individual subfield is a topic and genre facet.
If the subfield is 'a' OR 'v' then the individual subfield is a genre facet.
If the subfield is 'z' then the individual subfield is a geographic and genre facet.
If the subfield is 'y' then era and genre
If the subfield is 'b' OR 'x' then the individual subfield is a genre facet.

### Within the 690 field:
If the subfield is 'a' OR >= 'x' AND <= 'z' then those subfields are combined into the full subject. This will happen for each instance of the 690.
