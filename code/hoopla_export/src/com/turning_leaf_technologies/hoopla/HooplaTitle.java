package com.turning_leaf_technologies.hoopla;

class HooplaTitle {
    private long id;
    private long hooplaId;
    private long checksum;
    private boolean active;

    HooplaTitle(long id, long hooplaId, long checksum, boolean active) {
        this.id = id;
        this.hooplaId = hooplaId;
        this.checksum = checksum;
        this.active = active;
    }

    long getId() {
        return id;
    }

    long getHooplaId() {
        return hooplaId;
    }

    long getChecksum() {
        return checksum;
    }

    boolean isActive() {
        return active;
    }
}
