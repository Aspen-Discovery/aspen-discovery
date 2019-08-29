package com.turning_leaf_technologies.indexing;

public class IlsTitle {
    private Long checksum;
    private Long dateFirstDetected;

    public IlsTitle(Long checksum, Long dateFirstDetected) {
        this.checksum = checksum;
        this.dateFirstDetected = dateFirstDetected;
    }

    public Long getChecksum() {
        return checksum;
    }

    public Long getDateFirstDetected() {
        return dateFirstDetected;
    }
}
