## IP Addresses

- Within Account -> Primary Configuration -> Underneath Library Systems you can define IP addresses for your library and/or library branches

![IP Address Configuration](/manual/images/IP-Addresses.png)

- It is a great idea to define these IP addresses because it allows your Aspen Discovery layer to know when a patron is searching from within the library. To define an IP address, choose the 'Add New' button and input the address, Display Name of the branch, and from the Location dropdown choose which branch the IP address is assigned to. 

- You can input a range of IPs. For example 100.xxx.001-100.xxx.299

- When you input an IP address or range of IP addresses, you have three options to enable for those IPs: 'Treat as a Public OPAC',
'Block Access from this IP', or 'Allow API Access'. 
  - When Aspen sees a patron is searching from a defined branch IP address that has 'Treat as a Public OPAC' enabled, the language of the   catalog will change for items that live at the branch where they are searching. For example, you may see things change from available at Main Library to available here! The 'treat as public OPAC' option, also forces the user to be logged out of Aspen after a defined time period. This ensures the patrons account is protected when logged into your library computers.
  - 'Block Access from this IP' gives you the ability to block an IP that is affecting your sites performance
  - 'Allow API Access' allows the defined IP address to use Aspen through an API connection to get information 

- To find your current IP as well as information where Aspen thinks you are, you can navigate to http://_yoursite_/Help/IP. 

## Video Tutorial

[![IP Addresses](/manual/images/IP-Addresses.jpg)](https://youtu.be/BHVNk5EPBOc)

[Watch on Youtube](https://youtu.be/BHVNk5EPBOc)
