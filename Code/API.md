# Methods in individual files

### fill_species_dropdmenu.php:
  * fill_option($result = null): void - creating selection options in dropdown menu;
    - params: $result - a result set identifier returned by mysqli_query(), mysqli_store_result() or mysqli_use_result()

### main.js:
  * updateFileUploadContent(colors, files, data): void - controlling the creation and display of the corresponding controls and buttons; launched on main window's loading; 
    - params: colors - colours of the colorPicker instances, files - number of files passed by user, data - all data sent by user
  * dataContentChange(): void - checking whether the "Submit" button will be activated
  * hideLoadSampleButtons(): void - hiding "autoLoadButton" and "autoLoadDbButton" buttons
  * showLoadSampleButtons(): void - showing "autoLoadButton" and "autoLoadDbButton" buttons
  * activateSubmitButton(): void - activating the "Submit" button
  * deactivateSubmitButton(): void - deactivating the "Submit" button
  * resetSectionUploadData(): void - reseting (eg. cleaning textareas) the "UploadData" div where user has the option to upload files or text data
  * createColorPicker(i, color): HTMLInputElement - creating colorPicker instance where user can choose appropriate colour for an experiment;
    - params: i - id of colorPicker field, color - selected colour
  * createExperimentName(i): HTMLInputElement - creating experimentName instance where user can choose appropriate name for an experiment;
    - params: i - id of experimentName field
  * createUploadFileInput(i): HTMLInputElement - creating uploadFileInput instance where user can upload his data files to DiVenn;
    - params: i - id of uploadFileInput field
  * recoverTextData(filenums, data): void - recovering data to the main window (homepage) thanks to it the user does not need to input once again any previous experiment data;
    - params: filenums - number of textareas (experiments) created on the homepage, data - all data sent by user
  * onClickAutoLoadButton(): void - called on clicking the "autoLoadButton" button; based on number of the experiments it sends request by AJAX to the server and receives data and fills the appropriate textarea on the homepage; at the end of running this function, the "Submit" button is activated
  * onClickAutoLoadDbButton(): void - called on clicking the "autoLoadDbButton" button; based on number of the experiments it sends request by AJAX to the server and receives data and fills the appropriate textarea on the homepage;  at the end of running this function, the "Submit" button is activated
  * showErrors(errors): void - showing errors on the modal window;
    - params: errors - array of the saved errors
  * cutoffClicked(): void - called after on clicking the "cutoff" field;  it's completing initial values in the UI widget from where user can select one of them
  * cutoffChanged(): void - validating a number entered by user


### main.php:
  * initialization_session_vars(): void - initializing session variables at the start of script execution
  * initialization_session_vars_after_request(): void - setting session variables after request POST sending
  * initialize_filelist(): void - initializing file list if a number of files is greater than 0
  * initialization_session_vars_after_submit(): void - setting session variables after request submit a form on the homepage


### redraw_query.php:
  * test_input($data): string - preparing data passed as a parameter in this way: deleting whitespaces at the beginning and the end of string, strip slashes, replacing some characters and finally converting special characters to entities;
    * params: $data - data need to be processed


### showdata.php:
  * hideAllLabels(): void - hiding all labels (or symbols) displayed by the nodes on the graph
  * showAllLabels(): void - showing all labels by the nodes on the graph
  * showAllSymbols(): void - showing all gene symbols by the nodes on the graph
  * showGeneSymbol(d): void - showing gene symbol by the node "d" on the graph;
    - params: d - reference to the passed object (from the graph)
  * showLabel(d): void - showing label with id of the node "d" on the graph;
    - params: d - reference to the passed object (from the graph)
  * hideLabel(d): void - hiding label with id of the node "d" on the graph;
    - params: d - reference to the passed object (from the graph)
  * ShowNodeDetails(elm,d,i): void - called by clicking option "Gene Detail" in the context menu;
    - params: d - reference to the passed object (from the graph)
  * showNodeDetailsModal(nodeId, nodeTarget): void - showing node details in modal window;
    - params: nodeId - reference to the passed object (from the graph), nodeTarget
  * ShowGroupDetails(elm, d, i): void - called by clicking option "Gene Group Detail" in the context menu;
    - params: d - reference to the passed object (from the graph)
  * ShowGroupDetailsModal(nodeId, nodeTarget): void - showing node group details in modal window;
    - params: nodeId - reference to the passed object (from the graph), nodeTarget
  * loadmore(nodeTarget): void - loading 15 more rows to group gene detail table;
    - params: nodeTarget - reference to the passed object (from the graph)
  * loadall(nodeTarget): void - loading a whole group gene detail table;
    - params: nodeTarget - reference to the passed object (from the graph)
  * exportGeneDetail(nodeTarget): void - downloading gene group detail list;
    - params: nodeTarget - reference to the passed object (from the graph)
  * setCookie(name, value, days): void - setting cookie for recording show options;
    - params: name - name of a cookie, value - value of a cookie, days - number of active days of a cookie
  * getCookie(name): string|null - getting cookie with a given name and returning it or null;
    - params: name - name of a cookie
  * eraseCookie(name): void - erasing a cookie;
    - params: name - name of a cookie
  * lbstyle(val): void - hiding and showing lable;
    - params: val - flag determining whether to show or hide all labels
  * update(): void - updating all items in the window; launched on the "drawing" window's loading;
  * FizzyText(): void - initializing GUI variables and methods at the start of script execution
  * geneNumbers(value): void - showing overlapping gene numbers;
    - params: value - value of a cookie
  * unSum(sumShape, myNode): void - changing Sum nodes back to original;
    - params: sumShape, myNode
  * SumNodes(value): void - summarizing each group of nodes to one node, show numbers of up-regulated&down-regulated;
    - params: value - determines whether summarize or not
  * download(source, filename, type): void - saving svg graph to svg or png;
    - params: source - string representing serialized XML file, filename - target file name, type - file extension as 'svg', 'png' or 'jpg'
  * save(type): void - saving graph in a given type;
    - params: type - file extension as 'svg', 'png' or 'jpg'
  * saveonline(): void - saving graph in a given type by redirecting to the online converter: https://image.online-convert.com/convert-to-png;
  * ChangeNodeColor(): void - submitting changeColorForm
  * updateColorSelector(): void - updating color selectors
  * changeFontSize(val): void - changing font size;
    - params: val - value of a cookie
  * ChangeNodeShape(): void - changing shape form
  * updateShapeSelector(type, value): void - updating shape selector;
    - params: type, value
  * ShowPathwayDetailTable(): void - showing pathway detail table at the bottom of the "drawing" window
  * ShowGoDetailTable(): void - showing gene ontology detail table at the bottom of the "drawing" window
  * ShowDataTable(): void - redirecting to window "datatable2.html"
  * getSelected(type): void - returning (according to type) a value of the table of ontologies or pathways;
    - params: type - type of table ("Ontology" or "Pathway")