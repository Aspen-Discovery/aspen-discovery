package com.turning_leaf_technologies.indexing;

public class SideLoadScope {
    private long id;
    private String name;
    private long sideLoadId;
    private boolean restrictToChildrensMaterial;

    public long getId() {
        return id;
    }

    public void setId(long id) {
        this.id = id;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public boolean isRestrictToChildrensMaterial() {
        return restrictToChildrensMaterial;
    }

    void setRestrictToChildrensMaterial(boolean restrictToChildrensMaterial) {
        this.restrictToChildrensMaterial = restrictToChildrensMaterial;
    }

    long getSideLoadId() {
        return sideLoadId;
    }

    void setSideLoadId(long sideLoadId) {
        this.sideLoadId = sideLoadId;
    }
}
