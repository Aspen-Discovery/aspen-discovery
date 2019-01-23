<?php
/*
ADOBE SYSTEMS INCORPORATED
 Copyright 2009, Adobe Systems Incorporated
 All Rights Reserved.

NOTICE:  Adobe permits you to use, modify, and distribute this file in accordance with the 
terms of the Adobe license agreement accompanying it.  If you have received this file from a 
source other than Adobe, then your use, modification, or distribution of it requires the prior 
written permission of Adobe.

****************************************************************************************************

The signature is based on the SHA1 digest of the serialization of the infoset of the element being 
signed and all its attributes, children and children's attributes (not including the signature element 
itself). HMAC is based on the shared secret.

The infoset is serialized in the following way:
	1.	All adjacent text nodes are collapsed and their leading and trailing whitespace is removed.
	2.	Zero-length text nodes are removed.
	3.	Signature elements in Adept namespace are removed.
	4.	Attributes are sorted first by their namespaces and then by their names; sorting is done 
	    bytewise on UTF-8 representations.
	4a. If attributes have no namespace insert a 0 length string (ie 2 bytes of 0) for the namespace    
	5.	Strings are serialized by writing two-byte length (in big endian order) of the UTF-8 
	    representation and then UTF-8 representation itself
	6.	Long strings (longer than 0x7FFF) are broken into chunks: first as many strings of the 
	    maximum length 0x7FFF as needed, then the remaining string. This is done on the byte level, 
	    irrespective of the UTF-8 boundary.
	7.	Text nodes (text and CDATA) are serialized by writing TEXT_NODE byte and then text node value.
	8.	Attributes are serialized by writing ATTRIBUTE byte, then attribute namespace (empty string 
	    if no namespace), attribute name, and attribute value.
	9.	Elements are serialized by writing BEGIN_ELEMENT byte, then element namespace, element name, 
	    all attributes END_ATTRIBUTES byte, all children, END_ELEMENT byte.
*/

	function attributeCompare( $xmlAttrib1, $xmlAttrib2 )
	{
	// sort by Namespace and then by Name
	// since nodeName returns fully qualified name, this should work
		return strcmp( $xmlAttrib1->nodeName, $xmlAttrib2->nodeName );
	}
		
	class XMLSigningSerializer {
		var $BEGIN_ELEMENT = 1;
		var $END_ATTRIBUTES = 2;
		var $END_ELEMENT = 3;
		var $TEXT_NODE = 4;
		var $ATTRIBUTE = 5;
		
		var $verbose = true;

		function serializeString( $string, $isTextNode )
		{
			$outString = trim( $string );
			$len = strlen( $outString );

			if( $len == 0 )
			{
				// empty string
				//$outString = $nodeId . chr(0) . chr(0);
				$outString = chr(0) . chr(0);
			} 
			else
			{
				if( $len > 0x7FFF )
				{
					if( $this->verbose ) 
						echo "<p>Long String: $len</p>";
					$curPos = 0;
					$left = $len;
					$newOut = "";
					$sep = "00";
					$sep[0] = chr(0x7f);
					$sep[1] = chr(0xff);
					$tmp = 0;
					
					while( $left > 32767)
					{					
						if( $this->verbose )
							echo "<p>Chunking position: $curPos </p>";
						if( $isTextNode )
							$newOut .= $this->TEXT_NODE;
						$newOut .= $sep;
						$newOut .= substr( $outString, $curPos,  32767);
						$curPos +=  32767;
						$left -=  32767;
						$tmp = strlen( $newOut );
						if( $this->verbose ) 
							echo "<p>newOut Length now: $tmp </p>";

					}
		
		
					if( $left > 0 )
					{
						if( $this->verbose ) 
							echo "<p>Final Piece position: $left </p>";
						$sep[0] = chr(floor( $left / 256 ));
						$sep[1] = chr( $left % 256 );
						if( $isTextNode )
							$newOut .= $this->TEXT_NODE;
						$newOut .= $sep;
						$newOut .= substr( $outString, $curPos, $left );
						$tmp = strlen( $newOut );
						if( $this->verbose ) 
							echo "<p>newOut Length now: $tmp </p>";
					}
					
					$outString = $newOut;
				} 
				else 
				{
					$outString = chr(0) . chr(0) . $outString;
					$outString[0] = chr(floor( $len / 256 ));
					$outString[1] = chr( $len % 256 );
				}
				
			}

			
			
			if( $this->verbose )
			{
				$test3 = "|" . $outString . "|";
				echo "<p>outString $test3 length = " . strlen($outString) . " byte1 = " . ord($test3[1]) . " byte2 = " . ord($test3[2]) .  " | " .  ord( chr( $len % 256 ) ) ."</p>";
			}

			return $outString;
		}

		function serializeNodeName( $xmlNode )
		{
			if( $this->verbose )
				echo "<p>Getting name for $xmlNode->nodeName namespaceURI -$xmlNode->namespaceURI- 
					  prefix -$xmlNode->prefix-  localName -$xmlNode->localName- baseURI -$xmlNode->baseURI-</p>";
			
			$nameSerialization = NULL;
		
			$nameSerialization .= $this->serializeString($xmlNode->namespaceURI, false);
		
			$nameSerialization .= $this->serializeString( $xmlNode->localName, false );
			
			return $nameSerialization;
		}
		
		
		function serializeAttribute( $xmlAttribute )
		{
			if( $this->verbose ) 
				echo "<p>Serializing attribute - $xmlAttribute->nodeName - $xmlAttribute->nodeValue </p>";
			
			$attrib = $this->ATTRIBUTE;
		
			$attrib .= $this->serializeNodeName($xmlAttribute);
			
			$attrib .= $this->serializeString( $xmlAttribute->nodeValue, false );
			
			return  $attrib;
		}
		
		function serializeAttributes( $xmlNode )
		{
			if( $this->verbose )
				echo "<p>Serializing Attributes for $xmlNode->nodeName </p>";
			
			$serialization = NULL;
			
			if( $xmlNode->hasAttributes() )
			{
				$i =0;
				$xmlAttributes = NULL;
			
			// copy all of the attributes into an array
				foreach( $xmlNode->attributes as $xmlAttribute )
				{
					$xmlAttributes[$i]=$xmlAttribute;
					$i++;
				}
		
				if( $i > 0 )
				{
					// sort Attributes by Namespace and then by Name
					usort( $xmlAttributes, 'attributeCompare' );
				
					for( $j=0; $j < $i; $j++ )
					{
						$xmlAttribute = $xmlAttributes[$j];
						$serialization .= $this->serializeAttribute( $xmlAttribute );
					}
				}
			}
			
			$serialization .= $this->END_ATTRIBUTES;

			if( $this->verbose )
				echo "<p>Finished Serializing Attributes:  -$serialization- </p>";
		
			return $serialization;			
		}
		
		function serializeElement( $xmlElement )
		{

			if( $this->verbose )
				echo "<p>Serializing Element:  $xmlElement->nodeName</p>";

			$entitySerialization = $this->BEGIN_ELEMENT ;
				
			$entitySerialization .= $this->serializeNodeName( $xmlElement );
			$entitySerialization .= $this->serializeAttributes( $xmlElement );
					
			$foundChildEntityNodes = false;
			foreach(  $xmlElement->childNodes as $childNode )
			{
				if( $this->verbose )
					echo "<p>Inspecting child node $childNode->nodeName with Type: $childNode->nodeType</p>";
		
				if( $childNode->nodeType == XML_ELEMENT_NODE )
				{
					$foundChildEntityNodes = true;
					$entitySerialization .= $this->serializeElement( $childNode );
				}
			}
				
			if( (strlen($xmlElement->nodeValue) > 0) && !$foundChildEntityNodes )
			{
				if( $this->verbose )
					echo "<p>Node Value for $xmlElement->nodeName is: $xmlElement->nodeValue</p>";
							
				$entitySerialization .=  $this->serializeString(  $xmlElement->nodeValue, true );
			}
		

			$entitySerialization .= $this->END_ELEMENT;
			
			if( $this->verbose )
				echo "<p> Done Serializing Element $xmlElement->nodeName - serialization: $entitySerialization </p>";
				
			return $entitySerialization;
		}
		
		function serialize( $xmlElement )
		{
			if( $this->verbose )
				echo "<p>Starting Serialization</p>";
			$retval = $this->serializeElement( $xmlElement );
			if( $this->verbose )
				echo "<p>Finished Serialization</p>";
			return $retval;
		}
		
		function XMLSigningSerializer( $verbose )
		{
			$this->BEGIN_ELEMENT = chr(1);
			$this->END_ATTRIBUTES = chr(2);
			$this->END_ELEMENT = chr(3);
			$this->TEXT_NODE = chr(4);
			$this->ATTRIBUTE = chr(5);

			// Verbose means to output debuging statements about the serialization
			$this->verbose = $verbose;
		}
	}
?>