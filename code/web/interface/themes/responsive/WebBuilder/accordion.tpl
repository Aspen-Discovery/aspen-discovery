<div class="col-xs-12">
        <div class="panel customAccordian" id="{$id}-Panel">
            <a data-toggle="collapse" href="#{$id}-PanelBody">
                <div class="panel-heading">
                    <div class="panel-title">
                        {$title}
                    </div>
                </div>
            </a>
            <div id="{$id}-PanelBody" class="panel-collapse collapse">
                <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-9 col-sm-9 col-md-9 col-lg-10">
                                <div>
                                    {$contents}
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
</div>