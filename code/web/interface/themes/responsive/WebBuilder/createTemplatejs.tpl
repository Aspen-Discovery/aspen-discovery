<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE html>

<head>
    <title>Create Template</title>
    <meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/css/grapes.min.css" integrity="sha512-F+EUNfBQvAXDvJcKgbm5DgtsOcy+5uhbGuH8VtK0ru/N6S3VYM9OHkn9ACgDlkwoxesxgeaX/6BdrQItwbBQNQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/grapes.min.js" integrity="sha512-TavCuu5P1hn5roGNJSursS0xC7ex1qhRcbAG90OJYf5QEc4C/gQfFH/0MKSzkAFil/UBCTJCe/zmW5Ei091zvA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>
    <script src="https://unpkg.com/grapesjs-script-editor"></script>
    <script src="https://unpkg.com/grapesjs-plugin-forms"></script>
    <script src="https://unpkg.com/grapesjs-preset-webpage@1.0.2"></script>
    <script src="https://unpkg.com/grapesjs-tabs@1.0.6"></script>
    <script src="https://unpkg.com/grapesjs-custom-code@1.0.1"></script>
    <script src="https://unpkg.com/grapesjs-parser-postcss@1.0.1"></script>
    <script src="https://unpkg.com/grapesjs-tooltip@0.1.7"></script>
    <script src="https://unpkg.com/grapesjs-tui-image-editor@0.1.3"></script>
    <script src="https://unpkg.com/grapesjs-typed@1.0.5"></script>
    <script src="https://unpkg.com/grapesjs-style-bg@2.0.1"></script>
</head>

<body>
    <div id="gjs"></div>
    <div id="template-data" style="display: none;"></div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const templateId = urlParams.get('id');
        const editor = grapesjs.init({
            container: "#gjs",
            showOffsets: 1,
            noticeOnUnload: 0,
            storageManager: {
                autoload: 0,
                type: 'remote',
                stepsBeforeSave: 1,
                contentTypeJson: true,
                storeComponents: true,
                storeStyles: true,
                storeHtml: true,
                storeCss: true,
                headers: {
                    'Content-Type': 'application/json',
                },
            },
            plugins: [
          'gjs-blocks-basic', 
          'grapesjs-script-editor',
          'grapesjs-plugin-forms',
          'grapesjs-preset-webpage',
          'grapesjs-tabs',
          'grapesjs-custom-code',
          'grapesjs-parser-postcss',
          'grapesjs-tooltip',
          'grapesjs-tui-image-editor',
          'grapesjs-style-bg',
          'grapesjs-typed',
        ],
        pluginsOpts: {
          'gjs-blocks-basic': {},
          'grapesjs-script-editor': {},
          'grapesjs-plugin-forms': {},
          'grapesjs-preset-webpage': {},
          'grapesjs-tabs': {},
          'grapesjs-custom-code': {},
          'grapesjs-parser-postcss': {},
          'grapesjs-tooltip': {},
          'grapesjs-tui-image-editor': {},
          'grapesjs-style-bg': {},
          'grapesjs-typed': {}
        },
    
        });
        editor.Panels.addButton('options', [{
            id: 'save-as-template',
            className: 'fas fa-save',
            command: 'save-as-template',
            attributes: {
                title: 'Save as Template'
            }
        }]);
        editor.Commands.add('save-as-template', {
            run: function (editor, sender) {
                console.log('id: ', templateId);
                sender && sender.set('active', 0);
                let projectData = editor.getProjectData();
                let html = editor.getHtml();
                console.log(html);
                let css = editor.getCss();
                let pageData = {
                    templateId: templateId,
                    projectData: projectData,
                    html: html,
                    css: css,
                };
                
                var url = Globals.path + '/WebBuilder/AJAX?method=saveAsTemplate';
                $.ajax({
                    url: url,
                    type: "POST",
                    dataType: "json",
                    data: JSON.stringify({
                        "templateId": templateId,
                        "projectData": projectData,
                        "html": html,
                        "css": css,
                    }),
                    contentType: "application/json",
                    success: function (response) {
                        console.log('Saved as Template');
                    },
                    error: function (xhr, status, error) {
                        console.error('Error saving template: ', error);
                    }
                });
            }
        });
        editor.on('load', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const templateId = urlParams.get('id');
            const url = Globals.path + '/WebBuilder/AJAX?method=loadGrapesTemplate&id=' + templateId;
        
            $.ajax({
                url: url,
                type: 'GET',
                success: function (data) {
                    try {
                        if (data.success) {
                            editor.setComponents(data.html);
                            editor.setStyle(data.css);
                            editor.loadProjectData(data.projectData);
                        } else {
                            console.log('Error Loading Template: ', data.message);
                        }
                    } catch (e) {
                        console.error("Failed to parse JSON response: ", e);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AA=JAX call failed: ", textStatus, errorThrown);
                }
            });
        });
    </script>
    <style>
    .gjs-editor-cont {
        z-index: 1;
        position: relative;
    }
    .fa-save {
      background-color: green;
      border: 1px solid green;
    }

    .gjs-block-label {
      font-size: 10px;
    }
    </style>
</body>

</html>