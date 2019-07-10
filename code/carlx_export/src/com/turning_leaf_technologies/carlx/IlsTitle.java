package com.turning_leaf_technologies.carlx;

class IlsTitle {
    private Long checksum;
    private Long dateFirstDetected;

    IlsTitle(Long checksum, Long dateFirstDetected) {
        this.checksum = checksum;
        this.dateFirstDetected = dateFirstDetected;
    }

    Long getChecksum() {
        return checksum;
    }

    Long getDateFirstDetected() {
        return dateFirstDetected;
    }
}
