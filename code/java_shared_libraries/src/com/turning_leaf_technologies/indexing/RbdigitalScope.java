package com.turning_leaf_technologies.indexing;

public class RbdigitalScope {
    private long id;
    private String name;
    private boolean includeEBooks;
    private boolean includeEAudiobook;
    private boolean includeEMagazines;
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

    public boolean isIncludeEBooks() {
        return includeEBooks;
    }

    void setIncludeEBooks(boolean includeEBooks) {
        this.includeEBooks = includeEBooks;
    }

    public boolean isIncludeEMagazines() {
        return includeEMagazines;
    }

    void setIncludeEMagazines(boolean includeEMagazines) {
        this.includeEMagazines = includeEMagazines;
    }

     public boolean isIncludeEAudiobook() {
        return includeEAudiobook;
    }

    void setIncludeEAudiobook(boolean includeEAudiobook) {
        this.includeEAudiobook = includeEAudiobook;
    }

    public boolean isRestrictToChildrensMaterial() {
        return restrictToChildrensMaterial;
    }

    void setRestrictToChildrensMaterial(boolean restrictToChildrensMaterial) {
        this.restrictToChildrensMaterial = restrictToChildrensMaterial;
    }

}
