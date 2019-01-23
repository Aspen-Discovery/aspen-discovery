<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

require_once 'DriverInterface.php';
require_once ROOT_DIR . '/sys/SIP2.php';
require_once ROOT_DIR . '/Drivers/Horizon.php';

class DCL extends HorizonAPI {
	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile){
		parent::__construct($accountProfile);
	}

	function translateFineMessageType($code){
		switch ($code){
			case "abs":       return "Automatic Bill Sent";
			case "acr":       return "Address Correction Requested";
			case "adjcr":     return "Adjustment credit, for changed";
			case "adjdbt":    return "Adjustment debit, for changed";
			case "balance":   return "Balancing Entry";
			case "bcbr":      return "Booking Cancelled by Borrower";
			case "bce":       return "Booking Cancelled - Expired";
			case "bcl":       return "Booking Cancelled by Library";
			case "bcsp":      return "Booking Cancelled by Suspension";
			case "bct":       return "Booking Cancelled - Tardy";
			case "bn":        return "Billing Notice";
			case "chgs":      return "Charges Misc. Fees";
			case "cr":        return "Claimed Return";
			case "credit":    return "Credit";
			case "damage":    return "Damaged";
			case "dc":        return "Debt Collection";
			case "dynbhm":    return "Dynix Being Held Mail";
			case "dynbhp":    return "Dynix Being Held Phone";
			case "dynfnl":    return "Dynix Final Overdue Notice";
			case "dynhc":     return "Dynix Hold Cancelled";
			case "dynhexp":   return "Dynix Hold Expired";
			case "dynhns":    return "Dynix Hold Notice Sent";
			case "dynnot1":   return "Dynix First Overdue Notice";
			case "dynnot2":   return "Dynix Second Overdue Notice";
			case "edc":       return "Exempt from Debt Collection";
			case "fdc":       return "Force to Debt Collection";
			case "fee":       return "ILL fees/Postage";
			case "final":     return "Final Overdue Notice";
			case "finalr":    return "Final Recall Notice";
			case "fine":      return "Fine";
			case "hcb":       return "Hold Cancelled by Borrower";
			case "hcl":       return "Hold Cancelled by Library";
			case "hclr":      return "Hold Cancelled & Reinserted in";
			case "he":        return "Hold Expired";
			case "hncko":     return "Hold Notification - Deliver";
			case "hncsa":     return "Hold - from closed stack";
			case "hnmail":    return "Hold Notification - Mail";
			case "hnphone":   return "Hold Notification - Phone";
			case "ill":       return "Interlibrary Loan Notification";
			case "in":        return "Invoice";
			case "infocil":   return "Checkin Location";
			case "infocki":   return "Checkin date";
			case "infocko":   return "Checkout date";
			case "infodue":   return "Due date";
			case "inforen":   return "Renewal date";
			case "l":         return "Lost";
			case "ld":        return "Lost on Dynix";
			case "lf":        return "Found";
			case "LostPro":   return "Lost Processing Fee";
			case "lr":        return "Lost Recall";
			case "msg":       return "Message to Borrower";
			case "nocko":     return "No Checkout";
			case "Note":      return "Comment";
			case "notice1":   return "First Overdue Notice";
			case "notice2":   return "Second Overdue Notice";
			case "notice3":   return "Third Overdue Notice";
			case "noticr1":   return "First Recall Notice";
			case "noticr2":   return "Second Recall Notice";
			case "noticr3":   return "Third Recall Notice";
			case "noticr4":   return "Fourth Recall Notice";
			case "noticr5":   return "Fifth Recall Notice";
			case "nsn":       return "Never Send Notices";
			case "od":        return "Overdue Still Out";
			case "odd":       return "Overdue Still Out on Dynix";
			case "odr":       return "Recalled and Overdue Still Out";
			case "onlin":     return "Online Registration";
			case "payment":   return "Fine Payment";
			case "pcr":       return "Phone Correction Requested";
			case "priv":      return "Privacy - Family permission";
			case "rd":        return "Request Deleted";
			case "re":        return "Request Expired";
			case "recall":    return "Item is recalled before due date";
			case "refund":    return "Refund of Payment";
			case "ri":        return "Reminder Invoice";
			case "rl":        return "Requested item lost";
			case "rn":        return "Reminder Billing Notice";
			case "spec":      return "Special Message";
			case "supv":      return "See Supervisor";
			case "suspend":   return "Suspension until ...";
			case "unpd":      return "Damaged Material Replacement";
			case "waiver":    return "Waiver of Fine";
			default:
				return $code;
		}
	}

	public function translateLocation($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array(
        'bkm' => 'Interlibrary Loan Department',
        'cap' => 'Castle Pines',
        'cr'  => 'Philip S. Miller',
        'hi'  => 'Highlands Ranch',
        'lou' => 'Louviers',
        'lt'  => 'Lone Tree',
        'online' => 'Online content',
        'pa'  => 'Parker',
        'rox' => 'Roxborough',
        'ts'  => 'Technical Services',
        'xtra'=> 'Location for 3rd Party'
        );
        return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : 'Unknown' ;
	}
	
	public function translateCollection($collectionCode){
		$collectionCode = strtolower($collectionCode);
		$collectionMap = array(
        'aebf' => 'Adult ebook fiction',
        'aebnf' => 'Adult ebook nonfiction',
        'eaeb' => 'Easy ebook (fiction & nonfiction)',
        'jebf' => 'Juv. ebook fiction',
        'jebnf' => 'Juv. ebook nonfiction',
        'yebf' => 'Ya ebook fiction',
        'yebnf' => 'Ya ebook nonfiction',

        'aeaf' => 'Adult eaudio fiction',
        'aeanf' => 'Adult eaudio nonfiction',
        'eaea' => 'Easy eaudio (fiction & nonfiction)',
        'jeaf' => 'Juv. eaudio fiction',
        'jeanf' => 'Juv. eaudio nonfiction',
        'yeaf' => 'Ya eaudio fiction',
        'yeanf' => 'Ya eaudio nonfiction',

        'aevf' => 'Adult evideo fiction',
        'aevnf' => 'Adult evideo nonfiction',
        'eaev' => 'Easy evideo (fiction & nonfiction)',
        'jevf' => 'Juv. evideo fiction',
        'jeavf' => 'Juv. evideo nonfiction',
        'yevf' => 'Ya evideo fiction',
        'yevnf' => 'Ya evideo nonfiction',

        'aem' => 'Adult emusic',
        'jem' => 'Juv. emusic',
        'yem' => 'Ya emusic',

        'b'        => 'Biography',
        'bce'      => 'Book Club Express',
        'cb'       => 'Cass-ad.biography',
        'cdb'      => 'Comp.disc-ad.biography',
        'cde'      => 'Comp.disc-ch.easy Fiction',
        'cder'     => 'Comp.disc-ch.easy Reader',
        'cdf'      => 'Comp.disc-ad.fiction',
        'cdjb'     => 'Comp.disc-juv.biography',
        'cdjf'     => 'Comp.disc-juv.fiction',
        'cdjnf'    => 'Comp.disc-juv.non-fiction',
        'cdnf'     => 'Comp.disc-adult Non-fiction',
        'cdwbjb'   => 'Comp.disc w.book Juv.Biography',
        'cdwbjf'   => 'Comp.disc W. Book Juv.fiction',
        'cdwbjnf'  => 'Comp.disc W. Book',
        'cdwbyf'   => 'Comp.disc W. Book YA fiction',
        'cdyb'     => 'Comp.disc-ya Biography',
        'cdyf'     => 'Comp.disc-ya Fiction',
        'cdynf'    => 'Comp.disc-ya Non-fiction',
        'ce'       => 'Cass W.book-ch.easy Fiction',
        'cer'      => 'Cass W.book-ch.easy Reader',
        'cf'       => 'Cass-ad.fiction',
        'cjb'      => 'Cass-juv.biography',
        'cjf'      => 'Cass-juv.fiction',
        'cjnf'     => 'Cass-juv.non-fiction',
        'clc'      => 'Classics Collection',
        'cnf'      => 'Cass-ad.non-fiction',
        'com'      => 'Comic Book',
        'cwbjb'    => 'Cass W.book-juv.biography',
        'cwbjf'    => 'Cass W.book-juv.fiction',
        'cwbjnf'   => 'Cass W.book-juv.non-fiction',
        'cwbyf'    => 'Cass W.book-ya Fiction',
        'cyb'      => 'Cass-ya Biography',
        'cyf'      => 'Cass-ya Fiction',
        'cynf'     => 'Cass-ya Non-fiction',
        'e'        => 'Picture Book',
        'em'       => 'EMedia',
        'er'       => 'Easy Reader Book',
        'f'        => 'Ad.fiction',
        'fe'       => 'Fast Entry',
        'fl'       => 'Foreign language set material',
        'game'     => 'Video game',
        'hpcdnf'   => 'Help In Parenting',
        'hpmcd'    => 'Help In Parenting Music',
        'hpnf'     => 'Help In Parenting-non-fiction',
        'hpvdnf'   => 'Help In Parenting',
        'hpvnf'    => 'Help In Parenting',
        'hz'       => 'Hi Bus.coll.-non-fiction',
        'hzcd'     => 'Hi',
        'ill'      => 'Interlibrary Loan',
        'jb'       => 'Juv.biography',
        'jdk'      => 'Juv.discovery Kit-non-fiction',
        'jf'       => 'Juv.fiction',
        'jgn'      => 'Juv. Graphic Novel',
        'jlk'      => 'Juv.leapfrog Reading Kit',
        'jnf'      => 'Juv.non-fiction',
        'knf'      => 'Adult kit non-fiction',
        'lh'       => 'Local History',
        'ltb'      => 'Large Type-ad.biography',
        'ltf'      => 'Large Type-ad.fiction',
        'ltjf'     => 'Large Type-juv.fiction',
        'ltnf'     => 'Large Type-ad.non-fiction',
        'mc'       => 'Music Cass-ad.',
        'mcd'      => 'Music Comp.disc-ad.',
        'mcdj'     => 'Music Comp.disc-juv.',
        'mcdy'     => 'Music Comp.disc-ya',
        'mcj'      => 'Music Cass-juv.',
        'nbsb'     => 'New Book Shelf - Ad. Biography',
        'nbsf'     => 'New Book Shelf - Fiction',
        'nbsnf'    => 'New Book Shelf - Non-Fiction',
        'nf'       => 'Ad.non-fiction',
        'oo'       => 'Items On Order',
        'pac'      => 'Parker Classics Collection',
        'pam'      => 'Pamphlet',
        'pbf'      => 'Ad.paperback Fiction',
        'pbjf'     => 'Juv.paperback Fiction',
        'pbyf'     => 'Ya Paperback Fiction',
        'per'      => 'Periodical',
        'ph'       => 'LH Photographs',
        'plb'      => 'Playaway Adult Biography',
        'plf'      => 'Playaway Adult Fiction',
        'pljf'     => 'Playaway Juv. Fiction',
        'plnf'     => 'Playaway Adult Non-fiction',
        'plyf'     => 'Playaway Young Adult Fiction',
        'prf'      => 'Professional collection',
        'ref'      => 'Ad.reference',
        'refj'     => 'Juv.reference',
        'refmf'    => 'Ref (Ad. microform)',
        'refmp'    => 'Ref (Ad. map)',
        'refy'     => 'Ya Reference',
        'rmf'      => 'Cdrom-ad.fiction',
        'rmjf'     => 'Cdrom-juv.fiction',
        'rmjnf'    => 'Cdrom-juv.non-fiction',
        'rmnf'     => 'Cdrom-ad.non-fiction',
        'rref'     => 'Ready Reference',
        'spb'      => 'Spanish-ad.biography',
        'spcde'    => 'Spanish-comp.disc.-ch.easy',
        'spcdf'    => 'Spanish-comp.disc-ad.fiction',
        'spcdnf'   => 'Spanish-comp.disc-ad.non-ficti',
        'spce'     => 'Spanish-cass W.book-ch.easy',
        'spcf'     => 'Spanish-cass-ad.fiction',
        'spcjf'    => 'Spanish-cass-juv.fiction',
        'spcnf'    => 'Spanish-cass-ad.non-fiction',
        'spe'      => 'Spanish-ch.easy Fiction',
        'sper'     => 'Spanish-easy reader book',
        'spf'      => 'Spanish-ad.fiction',
        'spjb'     => 'Spanish-juv.biography',
        'spjf'     => 'Spanish-juv.fiction',
        'spjlk'    => 'Spanish-Juv.LeapFrog Reading',
        'spjnf'    => 'Spanish-juv.non-fiction',
        'spnf'     => 'Spanish-ad.non-fiction',
        'spr'      => 'Spanish-ad.reference',
        'sprj'     => 'Spanish-Juv Reference',
        'spvdjf'   => 'Spanish DVD-juv.fiction',
        'spvdnf'   => 'Spanish DVD-ad.non-fiction',
        'spvf'     => 'Spanish Video-ad.fiction',
        'spvjf'    => 'Spanish Video-juv.fiction',
        'spvjnf'   => 'Spanish Video-juv.non-fiction',
        'vspvnf'   => 'Spanish Video-ad.non-fiction',
        'spyf'     => 'Spanish-ya Fiction',
        'sto'      => 'Storytime Materials',
        'tin'      => 'Teen Interest Non-fiction',
        'tnf'      => 'Teen area non-fiction',
        'unk'      => 'Unknown collection for item',
        'vb'       => 'Video-ad.biography',
        'vdb'      => 'Dvd-ad.biography',
        'vdf'      => 'Dvd-ad.fiction',
        'vdjb'     => 'Dvd-juv.biography',
        'vdjf'     => 'Dvd-juv.fiction',
        'vdjnf'    => 'Dvd-juv.non-fiction',
        'vdnf'     => 'Dvd-ad.non-fiction',
        'vdyf'     => 'Dvd-ya Fiction',
        'vdynf'    => 'Dvd-ya Non-fiction',
        'vf'       => 'Video-ad.fiction',
        'vjb'      => 'Video-juv.biography',
        'vjf'      => 'Video-juv.fiction',
        'vjnf'     => 'Video-juv.non-fiction',
        'vnf'      => 'Video-ad.non-fiction',
        'vynf'     => 'Video-ya Non-fiction',
        'xcb'      => 'Ad.lit Circ-biography',
        'xccdf'    => 'Ad.lit.circ.comp.disc-fiction',
        'xccdnf'   => 'Ad.lit Circ',
        'vxccdwbf' => 'Ad.Lit.Circ.CD w.book-Fiction',
        'xccdwbn'  => 'Ad.Lit.Circ.CD',
        'xccnf'    => 'Ad.lit Circ Cass-non-fiction',
        'xcf'      => 'Ad.lit Circ-fiction',
        'xcnf'     => 'Ad.lit Circ-non-fiction',
        'xcrmnf'   => 'Ad.lit Circ Cdrom-non-fiction',
        'xcvdnf'   => 'Ad.Lit.Circ. DVD-Non-fiction',
        'xcvnf'    => 'Ad.lit Circ Video-non-fiction',
        'xcwbb'    => 'Ad.Lit.Circ.Cass',
        'xcwbf'    => 'Ad.Lit.Circ.Cass',
        'xcwbnf'   => 'Ad.Lit.Circ.Cass',
        'xlcdnf'   => 'Ad.lit.prog.comp.disc-non-fict',
        'xlcf'     => 'Ad.lit.prog.cass-fiction',
        'xlcnf'    => 'Ad.lit.prog.cass-non-fiction',
        'xlcwbf'   => 'Ad.lit.prog.cass',
        'xlcwbnf'  => 'Ad.lit.prog.cass',
        'xlnf'     => 'Ad.lit.prog.-non-fiction',
        'xlrmnf'   => 'Ad.lit.prog.cdrom-non-fiction',
        'xlvnf'    => 'Ad.lit.prog.video-non-fiction',
        'yb'       => 'Ya Biography',
        'yf'       => 'Ya Fiction',
        'ygn'      => 'Ya Graphic Novel',
        'ynf'      => 'Ya Non-fiction',
		);
		return isset($collectionMap[$collectionCode]) ? $collectionMap[$collectionCode] : 'Unknown';
	}
	
	public function translateStatus($statusCode){
		$statusCode = trim(strtolower($statusCode));
		$statusMap = array(
        'a'        => 'Archived',
        'ao'       => 'Acquisitions on',
        'ar'       => 'Just received',
        'b'        => 'Bindery',
        'bkmst'    => 'Bookmobile stored',
        'bso'      => 'Blue slip on order',
        'c'        => 'Claimed Returned',
        'ckod'     => 'Checked out on Dynix',
        'cpod'     => 'Castle Pines Opening',
        'cpst'     => 'Castle Pines Stored',
        'crcda'    => 'Castle Rock',
        'crda'     => 'Castle Rock Display',
        'crs'      => 'Castle Rock stored',
        'cryada'   => 'Castle Rock YA',
        'csa'      => 'Closed Stack',
        'del'      => 'Delete BC',
        'dero'     => 'Del BC/Reorder',
        'dmg'      => 'Damaged mode',
        'e'        => 'Item hold expired',
        'h'        => 'Item being held',
        'hich'     => 'HI Display - Kids',
        'hichibr'  => 'HI child browse',
        'hida'     => 'HI Display - 1st',
        'hiend'    => 'HI Dis - Kids\'',
        'hirdlng'  => 'HI Display Reading',
        'his'      => 'HI stored item',
        'hispcl'   => 'HI Dis - Kids\' spcl',
        'hiup'     => 'HI Display - 2nd',
        'i'        => 'Checked In',
        'ip'       => 'In Processing',
        'l'        => 'Lost',
        'loustr'   => 'LOU stored item',
        'lr'       => 'Lost Recall',
        'ltda'     => 'Lone Tree Display',
        'lts'      => 'Lone Tree stored',
        'm'        => 'Item missing',
        'mi'       => 'Missing Inventory',
        'mpbkm'    => 'BKM Missing Parts',
        'mpcc'     => 'Contact Center',
        'mphi'     => 'HI Missing Parts',
        'mplou'    => 'LOU Missing Parts',
        'mplt'     => 'LT Missing Parts',
        'mppa'     => 'PA Missing Parts',
        'mppsm'    => 'PSM Missing Parts',
        'mprox'    => 'ROX Missing Parts',
        'mpts'     => 'Technical Services',
        'n'        => 'Newly Acquired',
        'nlst'     => 'NL Stored Item',
        'o'        => 'Checked out',
        'online'   => 'Available online',
        'pada'     => 'Parker Display',
        'pas'      => 'Parker stored item',
        'r'        => 'On Order',
        'rb'       => 'Reserve Bookroom',
        'rda'      => 'Test record - no',
        'recall'   => 'Recall',
        'rw'       => 'Reserve withdrawal',
        's'        => 'Shelving Cart',
        'sort'     => 'Sorter Repackaging',
        't'        => 'In Cataloging',
        'tc'       => 'To Cataloging',
        'th'       => 'Transit Request',
        'tmcr'     => 'Temporary Reference',
        'tmhi'     => 'Temporary Reference',
        'tmjcr'    => 'Temporary Reference',
        'tmjhi'    => 'Temporary Reference',
        'tmjlt'    => 'Temporary Reference',
        'tmjpa'    => 'Temporary Reference',
        'tmlt'     => 'Temporary Reference',
        'tmpa'     => 'Temporary Reference',
        'tobe'     => 'Items to be ordered',
        'tr'       => 'Transit',
        'trace'    => 'Trace',
        'ufa'      => 'User fast added item',
        'wr'       => 'Waiting for',
        'wsd'      => 'Waiting at Service',
        'xcdrm'    => 'No Holds'
        );
        return isset($statusMap[$statusCode]) ? $statusMap[$statusCode] : 'Unknown';
	}
	
	public function getLocationMapLink($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array(
        'bkm' => '',
        'cap' => 'http://maps.google.com/maps?q=7437+Village+Square+Drive+%23110,+castle+rock,+CO+80108&iwloc=A&hl=en',
        'cr'  => 'http://maps.google.com/maps?q=100+S+Wilcox+St,+Castle+Rock,+CO+80104',
        'hi'  => 'http://maps.google.com/maps?q=9292+Ridgeline+Blvd+Highlands+Ranch,+CO+80129',
        'lou' => 'http://maps.google.com/maps?q=7885+Louviers+Blvd+Louviers+Colorado+80131',
        'lt'  => 'http://maps.google.com/maps?q=8827+Lone+Tree+Pkwy+Lone+Tree+Colorado+80124',
        'pa'  => 'http://maps.google.com/maps?q=10851+So.+Crossroads+Dr.+Parker+Colorado+80134',
        'rox' => 'http://maps.google.com/maps?q=8357+N+Rampart+Range+Rd+Littleton,+CO+80125-9365',
        'ts'  => '',
        'xtra'=> ''
        );
        return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : '' ;
	}
}