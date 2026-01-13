# Aspen Discovery API Documentation

## Overview

Aspen Discovery provides a comprehensive REST-like API that enables applications to interact with the library system. This API powers the Aspen LiDA mobile app and supports third-party integrations for user actions such as searching the catalog, placing holds, managing checkouts, and updating user profiles.

**Base URL Pattern:**
```
https://{aspen-url}/API/{APIName}?method={methodName}&{parameters}
```

**API Version:** All APIs are versioned and maintained in the `/code/web/services/API/` directory.

## Table of Contents

1. [Authentication](#authentication)
2. [Search Operations](#search-operations)
3. [Item/Record Information](#itemrecord-information)
4. [User Holds (Reservations)](#user-holds-reservations)
5. [Checkouts & Renewals](#checkouts--renewals)
6. [User Profile Management](#user-profile-management)
7. [Lists & Collections](#lists--collections)
8. [Reading History](#reading-history)
9. [Events](#events)
10. [Error Handling](#error-handling)
11. [Rate Limiting & Usage](#rate-limiting--usage)

---

## Authentication

The Aspen Discovery API supports three authentication methods:

### Method 1: Token-Based Authentication (Recommended)

Used primarily by the Aspen LiDA mobile app and trusted applications.

**Request Headers:**
```http
PHP_AUTH_USER: {base64_encoded_key1}
PHP_AUTH_PW: {base64_encoded_key2}
User-Agent: Aspen LiDA
Version: v1.x
LiDA-SessionID: {session_id}
```

**Token Generation:**
Tokens are generated and managed by the Aspen Discovery administrator through the system administration interface.

### Method 2: IP Address Whitelist

For server-to-server integrations where requests originate from known IP addresses.

**Configuration:**
IP addresses must be whitelisted in the Aspen Discovery administration panel. Requests from whitelisted IPs bypass token authentication.

### Method 3: User Credentials (Session-Based)

For user-specific operations, authentication is established through a login session.

**Login Endpoint:**
```
POST /API/UserAPI?method=login
```

**Parameters:**
- `username` - User's library barcode or username
- `password` - User's PIN or password

**Response:**
```json
{
  "result": {
    "success": true,
    "name": "John Doe",
    "session": "abc123xyz",
    "patronId": "12345"
  }
}
```

**Logout Endpoint:**
```
POST /API/UserAPI?method=logout
```

---

## Search Operations

The SearchAPI provides comprehensive search capabilities for discovering library materials.

**API Class:** `SearchAPI`
**File Location:** `/code/web/services/API/SearchAPI.php`

### Basic Search

Perform a full-text search across the catalog.

**Endpoint:**
```
GET /API/SearchAPI?method=search
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `lookfor` | string | Yes | Search query text |
| `searchSource` | string | No | Search source (e.g., "local", "all") |
| `searchIndex` | string | No | Index to search (e.g., "Keyword", "Title", "Author") |
| `page` | integer | No | Page number (default: 1) |
| `pageSize` | integer | No | Results per page (default: 20) |
| `sort` | string | No | Sort order (e.g., "relevance", "year desc", "title") |
| `filter[]` | array | No | Facet filters to apply |
| `library` | string | No | Library filter |
| `location` | string | No | Location filter |

**Example Request:**
```
GET /API/SearchAPI?method=search&lookfor=harry+potter&searchIndex=Keyword&page=1&pageSize=20&sort=relevance
```

**Response:**
```json
{
  "result": {
    "success": true,
    "recordCount": 150,
    "page": 1,
    "pageSize": 20,
    "records": [
      {
        "id": "123456",
        "title": "Harry Potter and the Sorcerer's Stone",
        "author": "J.K. Rowling",
        "format": "Book",
        "isbn": "9780439708180",
        "coverUrl": "https://aspen.example.com/bookcover.php?id=123456",
        "available": true,
        "availableCopies": 5,
        "totalCopies": 10
      }
    ],
    "facets": {
      "format": {
        "Book": 100,
        "eBook": 30,
        "Audiobook": 20
      },
      "availability": {
        "Available": 80,
        "Checked Out": 70
      }
    }
  }
}
```

### Lightweight Search (Mobile)

Optimized search for mobile applications with reduced data transfer.

**Endpoint:**
```
GET /API/SearchAPI?method=searchLite
```

**Parameters:** Same as basic search

**Response:** Similar to basic search but with minimal metadata per record.

### Get Available Facets

Retrieve all available facets for filtering search results.

**Endpoint:**
```
GET /API/SearchAPI?method=getAvailableFacets
```

**Response:**
```json
{
  "result": {
    "facets": [
      {
        "key": "format",
        "label": "Format",
        "values": [
          {"value": "Book", "count": 5000},
          {"value": "eBook", "count": 2000}
        ]
      },
      {
        "key": "availability",
        "label": "Availability",
        "values": [
          {"value": "Available Now", "count": 3000}
        ]
      }
    ]
  }
}
```

### Browse Categories

Browse curated collections and categories.

**Endpoint:**
```
GET /API/SearchAPI?method=getActiveBrowseCategories
```

**Response:**
```json
{
  "result": {
    "categories": [
      {
        "id": "1",
        "name": "New Fiction",
        "description": "Recently added fiction titles",
        "imageUrl": "https://aspen.example.com/category-image.jpg"
      }
    ]
  }
}
```

**Get Category Results:**
```
GET /API/SearchAPI?method=getBrowseCategoryResults&categoryId=1
```

### Search by ISBN/Barcode

Find records using ISBN or item barcode.

**Endpoint:**
```
GET /API/SearchAPI?method=getTitleInfoForISBN&isbn={isbn}
GET /API/SearchAPI?method=getRecordIdForItemBarcode&barcode={barcode}
```

---

## Item/Record Information

The ItemAPI provides detailed information about individual items and records.

**API Class:** `ItemAPI`
**File Location:** `/code/web/services/API/ItemAPI.php`

### Get Item Details

Retrieve comprehensive information about a specific item.

**Endpoint:**
```
GET /API/ItemAPI?method=getItemDetails
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | string | Yes | Record/grouped work ID |

**Example Request:**
```
GET /API/ItemAPI?method=getItemDetails&id=123456
```

**Response:**
```json
{
  "result": {
    "id": "123456",
    "title": "Harry Potter and the Sorcerer's Stone",
    "author": "J.K. Rowling",
    "isbn": "9780439708180",
    "publisher": "Scholastic",
    "publicationDate": "1998",
    "description": "Harry Potter has never been the star of a Quidditch team...",
    "format": ["Book", "eBook", "Audiobook"],
    "language": "English",
    "pages": 320,
    "coverUrl": "https://aspen.example.com/bookcover.php?id=123456",
    "rating": 4.5,
    "numberOfRatings": 1234,
    "series": "Harry Potter #1",
    "subjects": ["Fantasy", "Magic", "Adventure"],
    "availability": {
      "available": true,
      "availableCopies": 5,
      "totalCopies": 10,
      "onHoldCopies": 3
    },
    "relatedRecords": [
      {
        "id": "123457",
        "title": "Harry Potter and the Chamber of Secrets",
        "format": "Book"
      }
    ]
  }
}
```

### Get Item Availability

Check real-time availability status.

**Endpoint:**
```
GET /API/ItemAPI?method=getItemAvailability&id={id}
```

**Response:**
```json
{
  "result": {
    "id": "123456",
    "available": true,
    "availableCopies": 5,
    "totalCopies": 10,
    "onHoldCopies": 3,
    "locations": [
      {
        "location": "Main Library",
        "callNumber": "J FIC ROW",
        "available": 3,
        "checkedOut": 2
      }
    ]
  }
}
```

### Get Copy and Hold Counts

Retrieve copy and hold statistics.

**Endpoint:**
```
GET /API/ItemAPI?method=getCopyAndHoldCounts&id={id}
```

### Get Related Records

Find related editions, formats, and versions.

**Endpoint:**
```
GET /API/ItemAPI?method=getRelatedRecord&id={id}
```

### Get Book Cover

Retrieve cover image for a record.

**Endpoint:**
```
GET /API/ItemAPI?method=getBookCover&id={id}&size={small|medium|large}
```

---

## User Holds (Reservations)

The UserAPI provides comprehensive hold management functionality.

**API Class:** `UserAPI`
**File Location:** `/code/web/services/API/UserAPI.php`

### Place Hold

Place a hold (reservation) on an item.

**Endpoint:**
```
POST /API/UserAPI?method=placeHold
```

**Authentication:** Required (user session or token)

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `bibId` | string | Yes* | Bibliographic record ID |
| `recordId` | string | Yes* | Alternate record identifier |
| `pickupBranch` | string | Yes | Pickup location code |
| `sublocation` | string | No | Specific sublocation |
| `cancelDate` | string | No | Automatic cancellation date (YYYY-MM-DD) |

*One of `bibId` or `recordId` is required

**Example Request:**
```
POST /API/UserAPI?method=placeHold&bibId=123456&pickupBranch=MAIN&cancelDate=2026-12-31
```

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "Your hold was successfully placed.",
    "holdId": "H123456",
    "title": "Harry Potter and the Sorcerer's Stone",
    "author": "J.K. Rowling",
    "pickupLocation": "Main Library",
    "expirationDate": "2026-12-31",
    "position": 3,
    "estimatedWaitDays": 14
  }
}
```

**Error Response:**
```json
{
  "result": {
    "success": false,
    "message": "This item is not available for holds.",
    "errorCode": "ITEM_NOT_HOLDABLE"
  }
}
```

### Get Patron Holds

Retrieve all holds for the current user.

**Endpoint:**
```
GET /API/UserAPI?method=getPatronHolds
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `source` | string | No | Filter by source (e.g., "ils", "overdrive") |

**Response:**
```json
{
  "result": {
    "success": true,
    "holds": {
      "available": [
        {
          "holdId": "H123456",
          "id": "123456",
          "title": "Harry Potter and the Sorcerer's Stone",
          "author": "J.K. Rowling",
          "format": "Book",
          "coverUrl": "https://aspen.example.com/bookcover.php?id=123456",
          "status": "Available for pickup",
          "pickupLocation": "Main Library",
          "expirationDate": "2026-02-01",
          "createDate": "2026-01-01"
        }
      ],
      "unavailable": [
        {
          "holdId": "H123457",
          "id": "123457",
          "title": "Harry Potter and the Chamber of Secrets",
          "author": "J.K. Rowling",
          "format": "Book",
          "coverUrl": "https://aspen.example.com/bookcover.php?id=123457",
          "status": "In Transit",
          "position": 3,
          "totalHolds": 15,
          "pickupLocation": "Main Library",
          "createDate": "2026-01-10"
        }
      ],
      "frozen": []
    },
    "totalHolds": 2,
    "availableHolds": 1,
    "unavailableHolds": 1,
    "frozenHolds": 0
  }
}
```

### Cancel Hold

Cancel an existing hold.

**Endpoint:**
```
POST /API/UserAPI?method=cancelHold
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cancelId` | string | Yes | Hold ID to cancel |
| `recordId` | string | No | Record ID (for some ILS systems) |

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "Your hold was successfully cancelled."
  }
}
```

### Freeze Hold

Temporarily suspend a hold.

**Endpoint:**
```
POST /API/UserAPI?method=freezeHold
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `holdId` | string | Yes | Hold ID to freeze |
| `recordId` | string | Yes | Record ID |
| `reactivationDate` | string | No | Date to automatically reactivate (YYYY-MM-DD) |

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "Your hold was successfully frozen.",
    "reactivationDate": "2026-03-01"
  }
}
```

### Activate (Unfreeze) Hold

Resume a frozen hold.

**Endpoint:**
```
POST /API/UserAPI?method=activateHold
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `holdId` | string | Yes | Hold ID to activate |
| `recordId` | string | Yes | Record ID |

### Change Hold Pickup Location

Update the pickup location for a hold.

**Endpoint:**
```
POST /API/UserAPI?method=changeHoldPickUpLocation
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `holdId` | string | Yes | Hold ID to modify |
| `location` | string | Yes | New pickup location code |

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "Your pickup location was successfully updated.",
    "newLocation": "Branch Library"
  }
}
```

### Get Valid Pickup Locations

Retrieve available pickup locations for holds.

**Endpoint:**
```
GET /API/UserAPI?method=getValidPickupLocations
```

**Response:**
```json
{
  "result": {
    "locations": [
      {
        "code": "MAIN",
        "displayName": "Main Library",
        "address": "123 Library St"
      },
      {
        "code": "BRANCH",
        "displayName": "Branch Library",
        "address": "456 Oak Ave"
      }
    ]
  }
}
```

---

## Checkouts & Renewals

Manage checked out items and renewals.

### Get Checked Out Items

Retrieve all items checked out by the user.

**Endpoint:**
```
GET /API/UserAPI?method=getPatronCheckedOutItems
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `source` | string | No | Filter by source (e.g., "ils", "overdrive") |

**Response:**
```json
{
  "result": {
    "success": true,
    "checkouts": [
      {
        "checkoutId": "C123456",
        "id": "123456",
        "title": "Harry Potter and the Sorcerer's Stone",
        "author": "J.K. Rowling",
        "format": "Book",
        "coverUrl": "https://aspen.example.com/bookcover.php?id=123456",
        "checkoutDate": "2026-01-01",
        "dueDate": "2026-01-22",
        "renewalCount": 1,
        "canRenew": true,
        "maxRenewals": 3,
        "overdue": false,
        "daysUntilDue": 9,
        "barcode": "31234567890123"
      }
    ],
    "totalCheckouts": 1,
    "overdueCheckouts": 0
  }
}
```

### Renew Item

Renew a checked out item.

**Endpoint:**
```
POST /API/UserAPI?method=renewItem
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `recordId` | string | Yes | Record ID to renew |
| `itemId` | string | No | Item ID (for some ILS systems) |
| `itemIndex` | string | No | Item index |

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "Your item was successfully renewed.",
    "newDueDate": "2026-02-12",
    "renewalsRemaining": 2
  }
}
```

**Error Response:**
```json
{
  "result": {
    "success": false,
    "message": "This item cannot be renewed because it has holds.",
    "errorCode": "RENEWAL_BLOCKED_HOLDS"
  }
}
```

### Renew All Items

Renew all eligible checked out items.

**Endpoint:**
```
POST /API/UserAPI?method=renewAll
```

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "3 of 4 items were successfully renewed.",
    "renewedCount": 3,
    "totalCount": 4,
    "renewals": [
      {
        "title": "Harry Potter and the Sorcerer's Stone",
        "success": true,
        "newDueDate": "2026-02-12"
      },
      {
        "title": "The Great Gatsby",
        "success": false,
        "message": "Item has holds"
      }
    ]
  }
}
```

---

## User Profile Management

Manage user account information and preferences.

### Get Patron Profile

Retrieve user profile information.

**Endpoint:**
```
GET /API/UserAPI?method=getPatronProfile
```

**Response:**
```json
{
  "result": {
    "success": true,
    "profile": {
      "id": "12345",
      "firstName": "John",
      "lastName": "Doe",
      "displayName": "John Doe",
      "email": "john.doe@example.com",
      "phone": "555-1234",
      "barcode": "23456789012345",
      "address": {
        "street": "123 Main St",
        "city": "Anytown",
        "state": "ST",
        "zip": "12345"
      },
      "homeLocation": "Main Library",
      "expirationDate": "2027-01-01",
      "fineAmount": 5.50,
      "numCheckouts": 3,
      "numHolds": 2,
      "numOverdueCheckouts": 0,
      "noticePreference": "email",
      "readingHistoryEnabled": true,
      "accountSummary": {
        "totalCheckouts": 3,
        "totalHolds": 2,
        "totalFines": 5.50
      }
    }
  }
}
```

### Update Notification Preferences

Set notification preferences for holds, due dates, etc.

**Endpoint:**
```
POST /API/UserAPI?method=setNotificationPreference
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `notificationType` | string | Yes | Type of notification (e.g., "Hold", "DueDate") |
| `method` | string | Yes | Notification method ("email", "sms", "phone") |
| `enabled` | boolean | Yes | Enable or disable |

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "Your notification preferences were updated."
  }
}
```

### Get Notification Preferences

Retrieve current notification settings.

**Endpoint:**
```
GET /API/UserAPI?method=getNotificationPreferences
```

**Response:**
```json
{
  "result": {
    "success": true,
    "preferences": [
      {
        "type": "Hold Available",
        "email": true,
        "sms": false,
        "phone": false
      },
      {
        "type": "Item Due Soon",
        "email": true,
        "sms": true,
        "phone": false
      }
    ]
  }
}
```

---

## Lists & Collections

Manage user-created lists of library materials.

**API Class:** `ListAPI`
**File Location:** `/code/web/services/API/ListAPI.php`

### Get User Lists

Retrieve all lists created by the user.

**Endpoint:**
```
GET /API/ListAPI?method=getUserLists
```

**Response:**
```json
{
  "result": {
    "success": true,
    "lists": [
      {
        "id": "1",
        "title": "Summer Reading",
        "description": "Books to read this summer",
        "public": false,
        "numTitles": 12,
        "created": "2026-01-01",
        "modified": "2026-01-10"
      }
    ]
  }
}
```

### Create List

Create a new list.

**Endpoint:**
```
POST /API/ListAPI?method=createList
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | List title |
| `description` | string | No | List description |
| `public` | boolean | No | Make list public (default: false) |

**Response:**
```json
{
  "result": {
    "success": true,
    "listId": "2",
    "message": "Your list was successfully created."
  }
}
```

### Add Titles to List

Add items to an existing list.

**Endpoint:**
```
POST /API/ListAPI?method=addTitlesToList
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `listId` | string | Yes | List ID |
| `titleIds` | array | Yes | Array of record IDs to add |

**Example Request:**
```
POST /API/ListAPI?method=addTitlesToList&listId=1&titleIds[]=123456&titleIds[]=123457
```

**Response:**
```json
{
  "result": {
    "success": true,
    "message": "2 titles were added to your list.",
    "addedCount": 2
  }
}
```

### Get List Titles

Retrieve items in a list.

**Endpoint:**
```
GET /API/ListAPI?method=getListTitles&listId={listId}
```

**Response:**
```json
{
  "result": {
    "success": true,
    "listId": "1",
    "listTitle": "Summer Reading",
    "titles": [
      {
        "id": "123456",
        "title": "Harry Potter and the Sorcerer's Stone",
        "author": "J.K. Rowling",
        "format": "Book",
        "coverUrl": "https://aspen.example.com/bookcover.php?id=123456",
        "available": true
      }
    ],
    "totalTitles": 12
  }
}
```

### Remove Titles from List

Remove items from a list.

**Endpoint:**
```
POST /API/ListAPI?method=removeTitlesFromList
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `listId` | string | Yes | List ID |
| `titleIds` | array | Yes | Array of record IDs to remove |

### Delete List

Delete a list.

**Endpoint:**
```
POST /API/ListAPI?method=deleteList&listId={listId}
```

---

## Reading History

Track and manage reading history.

### Get Reading History

Retrieve user's reading history.

**Endpoint:**
```
GET /API/UserAPI?method=getPatronReadingHistory
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `pageSize` | integer | No | Results per page (default: 20) |
| `sort` | string | No | Sort order (e.g., "checkedOut", "title") |

**Response:**
```json
{
  "result": {
    "success": true,
    "readingHistory": [
      {
        "id": "123456",
        "title": "Harry Potter and the Sorcerer's Stone",
        "author": "J.K. Rowling",
        "format": "Book",
        "coverUrl": "https://aspen.example.com/bookcover.php?id=123456",
        "checkoutDate": "2026-01-01",
        "returnDate": "2026-01-15"
      }
    ],
    "totalRecords": 150,
    "page": 1,
    "pageSize": 20
  }
}
```

### Opt In to Reading History

Enable reading history tracking.

**Endpoint:**
```
POST /API/UserAPI?method=optIntoReadingHistory
```

### Opt Out of Reading History

Disable reading history tracking.

**Endpoint:**
```
POST /API/UserAPI?method=optOutOfReadingHistory
```

### Delete Reading History

Clear reading history.

**Endpoint:**
```
POST /API/UserAPI?method=deleteAllFromReadingHistory
```

**Delete Selected:**
```
POST /API/UserAPI?method=deleteSelectedFromReadingHistory
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `recordIds` | array | Yes | Array of record IDs to delete |

---

## Events

Discover and manage library events.

**API Class:** `EventAPI`
**File Location:** `/code/web/services/API/EventAPI.php`

### Get Event Details

Retrieve information about a specific event.

**Endpoint:**
```
GET /API/EventAPI?method=getEventDetails&eventId={eventId}
```

**Response:**
```json
{
  "result": {
    "success": true,
    "event": {
      "id": "E123",
      "title": "Summer Reading Kickoff",
      "description": "Join us for the start of our summer reading program!",
      "startDate": "2026-06-01T10:00:00",
      "endDate": "2026-06-01T12:00:00",
      "location": "Main Library - Community Room",
      "registrationRequired": true,
      "registrationOpen": true,
      "spotsAvailable": 25,
      "imageUrl": "https://aspen.example.com/event-image.jpg",
      "category": "Children's Program",
      "ageGroup": "Children",
      "source": "Library Calendar"
    }
  }
}
```

### Save Event

Save/favorite an event.

**Endpoint:**
```
POST /API/EventAPI?method=saveEvent&eventId={eventId}
```

### Get Saved Events

Retrieve user's saved events.

**Endpoint:**
```
GET /API/EventAPI?method=getSavedEvents
```

**Response:**
```json
{
  "result": {
    "success": true,
    "events": [
      {
        "id": "E123",
        "title": "Summer Reading Kickoff",
        "startDate": "2026-06-01T10:00:00",
        "location": "Main Library"
      }
    ]
  }
}
```

---

## Error Handling

All API responses follow a consistent error format.

### Success Response Format

```json
{
  "result": {
    "success": true,
    "data": { }
  }
}
```

### Error Response Format

```json
{
  "result": {
    "success": false,
    "message": "Human-readable error message",
    "errorCode": "ERROR_CODE"
  }
}
```

OR

```json
{
  "error": "Error message"
}
```

### Common Error Codes

| HTTP Code | Error Code | Description |
|-----------|------------|-------------|
| 401 | `unauthorized_access` | Authentication failed or token invalid |
| 403 | `forbidden` | IP address not whitelisted or insufficient permissions |
| 400 | `invalid_method` | Method doesn't exist or invalid parameters |
| 400 | `missing_parameter` | Required parameter not provided |
| 404 | `not_found` | Requested resource not found |
| 500 | `system_error` | Internal server error |

### Hold-Specific Error Codes

| Error Code | Description |
|------------|-------------|
| `ITEM_NOT_HOLDABLE` | Item is not available for holds |
| `HOLD_LIMIT_REACHED` | User has reached maximum hold limit |
| `HOLD_EXISTS` | User already has a hold on this item |
| `INVALID_PICKUP_LOCATION` | Pickup location is invalid |

### Checkout-Specific Error Codes

| Error Code | Description |
|------------|-------------|
| `CHECKOUT_LIMIT_REACHED` | User has reached maximum checkout limit |
| `ITEM_NOT_AVAILABLE` | Item is not available for checkout |
| `ACCOUNT_BLOCKED` | User account is blocked |

---

## Rate Limiting & Usage

### Usage Tracking

All API requests are logged and tracked for monitoring and analytics purposes.

**Tracked Information:**
- API module and method called
- Timestamp
- IP address
- User agent
- Response time

**Usage Dashboard:**
API usage statistics are available to administrators at:
```
https://{aspen-url}/API/UsageDashboard
```

### Best Practices

1. **Caching:** Cache search results and item details when appropriate to reduce API calls
2. **Pagination:** Use pagination for large result sets
3. **Batch Operations:** Use batch methods (e.g., `renewAll`) when available
4. **Error Handling:** Implement proper error handling and retry logic
5. **Token Security:** Keep API tokens secure and rotate them regularly

---

## OpenAPI Specifications

Aspen Discovery provides OpenAPI (Swagger) specifications for automated integration.

**Available Specifications:**
- Main API: `/code/web/openapi/aspen_openapi.json`
- User API: `/code/web/openapi/UserAPI_openapi.json`
- Event API: `/code/web/openapi/EventAPI_openapi.json`
- Work API: `/code/web/openapi/WorkAPI_openapi.json`

**Interactive Documentation:**
```
https://{aspen-url}/API/Documentation
```

---

## Example Workflows

### Complete Hold Workflow

1. **Search for an item:**
   ```
   GET /API/SearchAPI?method=search&lookfor=harry+potter
   ```

2. **Get item details:**
   ```
   GET /API/ItemAPI?method=getItemDetails&id=123456
   ```

3. **Check availability:**
   ```
   GET /API/ItemAPI?method=getItemAvailability&id=123456
   ```

4. **Get valid pickup locations:**
   ```
   GET /API/UserAPI?method=getValidPickupLocations
   ```

5. **Place the hold:**
   ```
   POST /API/UserAPI?method=placeHold&bibId=123456&pickupBranch=MAIN
   ```

6. **Verify hold was placed:**
   ```
   GET /API/UserAPI?method=getPatronHolds
   ```

### Checkout Management Workflow

1. **Get checked out items:**
   ```
   GET /API/UserAPI?method=getPatronCheckedOutItems
   ```

2. **Renew specific item:**
   ```
   POST /API/UserAPI?method=renewItem&recordId=123456
   ```

3. **Renew all eligible items:**
   ```
   POST /API/UserAPI?method=renewAll
   ```

---

## Additional Resources

**File Locations:**
- API Classes: `/code/web/services/API/`
- Base API Class: `/code/web/services/API/AbstractAPI.php`
- OpenAPI Specs: `/code/web/openapi/`

**Support:**
- For API access and token generation, contact your Aspen Discovery administrator
- For technical support, refer to the Aspen Discovery documentation

---

**Document Version:** 1.0
**Last Updated:** 2026-01-13
**Aspen Discovery Version:** 26.01.00+
