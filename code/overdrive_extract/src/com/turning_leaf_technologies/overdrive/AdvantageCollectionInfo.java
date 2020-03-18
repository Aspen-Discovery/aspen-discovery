package com.turning_leaf_technologies.overdrive;

class AdvantageCollectionInfo {
    private int advantageId;
    private String collectionToken;
    private long aspenLibraryId = 0;
    private String name;

    int getAdvantageId() {
        return advantageId;
    }

    void setAdvantageId(int advantageId) {
        this.advantageId = advantageId;
    }

    String getCollectionToken() {
        return collectionToken;
    }

    void setCollectionToken(String collectionToken) {
        this.collectionToken = collectionToken;
    }

    long getAspenLibraryId() {
        return aspenLibraryId;
    }

    void setAspenLibraryId(long aspenLibraryId) {
        this.aspenLibraryId = aspenLibraryId;
    }

    String getName() {
        return name;
    }

    void setName(String name) {
        this.name = name;
    }
}
