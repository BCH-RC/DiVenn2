function updateFileUploadContent(colors, files, data) {
    var default_colors = ["#a23388", "#ccffff", "#99cc33", "#ff9900", "#9966cc", "#0099cc", "#663300", "#39b5b8", "#ccbbaa", "#2022FE"];
    var selectedNumber = $('#exp_num').val();
    var uploadType = $("input[type='radio'][name='uploadtype']:checked").val();
    var color = null;
    resetSectionUploadData();

    for (var i = 0; i < selectedNumber; i++) {
        if (colors.length > 0)
            color = colors[i];
        else
            color = default_colors[i];

        var divcontent = document.createElement('div');
        divcontent.setAttribute("class", "form-group");

        divcontent.appendChild(createColorPicker(i, color));
        divcontent.appendChild(createExperimentName(i));

        if (uploadType === "UploadFile") divcontent.append(createUploadFileInput(i)); else divcontent.append(createUploadTextInput(i));
        $('#UploadData').append(divcontent);
    }
    if (selectedNumber === '0' || uploadType === "UploadFile") hideLoadSampleButtons();
    else if (uploadType === "UploadText") {
        showLoadSampleButtons();
        onClickAutoLoadButton();
        onClickAutoLoadDbButton();
    }
    if (files > 0 && data.length > 0) {
        recoverTextData(files, data);
        activateSubmitButton();
    }
    switchCutoffMode();
}

function updateSpeciesList(options_list) {
    var species = $("#species");
    species.empty();
    var notselectedOption = createOption('<please select>', 'notselected', true);
    species.append(notselectedOption);
    for (var i = 0; i < options_list.length; i++) {
        var option = createOption(options_list[i][1], options_list[i][0], false);
        species.append(option);
    }
}

function createOption(text, value, selected) {
    return new Option(text, value, selected);
}

function clearUploadTextInputs() {
    var selectedNumber = $('#exp_num').val();
    for (var i = 0; i < selectedNumber; i++) {
        $('#txtquery' + i).val('');
    }
}

function dataContentChange() {
    var selectedNumber = $("#exp_num").val();
    var uploadType = $("input[type='radio'][name='uploadtype']:checked").val();
    var enableSubmit = true;
    for (var i = 0; i < selectedNumber; i++) {
        var inputdata = '';
        if (uploadType === "UploadFile") inputdata = $("#file" + i).val();
        else inputdata = $("#txtquery" + i).val();

        if (inputdata.trim().length === 0) enableSubmit = false;
    }
    if (enableSubmit === true) activateSubmitButton();
}

function showLoadSampleButtons() {
    $('#autoLoadButton').removeClass('hidden');
    $('#autoLoadDbButton').removeClass('hidden');
}

function hideLoadSampleButtons() {
    $('#autoLoadButton').addClass('hidden');
    $('#autoLoadDbButton').addClass('hidden');
}

function activateSubmitButton() {
    $('#submit').prop('disabled', false);
}

function deactivateSubmitButton() {
    $('#submit').prop('disabled', true);
}

function showCutoffDiv() {
    $('#cutoff').prop('required', true);
    $('#cutoff_div').show();
}

function hideCutoffDiv() {
    $('#cutoff').prop('required', false);
    $('#cutoff_div').hide();
}

function resetSectionUploadData() {
    $('#UploadData').empty();
}

function switchCutoffMode() {
    var cutoffType = $("input[type='radio'][name='cutofftype']:checked").val();
    if (cutoffType === "CutoffAny") showCutoffDiv();
    else hideCutoffDiv();
}

function createColorPicker(i, color) {
    var jcolorinput = document.createElement('input');
    jcolorinput.setAttribute('class', 'jscolor');
    jcolorinput.setAttribute('id', 'colorselector' + i);
    jcolorinput.setAttribute('name', 'colorselector' + i);
    jcolorinput.setAttribute('value', color);
    var jColorPicker = new JSColor(jcolorinput, {format: 'hex'});
    jColorPicker.option({
        'width': 200, 'backgroundColor': '#333'
    });
    return jcolorinput;
}

function createExperimentName(i) {
    var experimenthidden = document.createElement('input');
    experimenthidden.setAttribute('class', 'experiment');
    experimenthidden.setAttribute('id', 'txt' + i);
    experimenthidden.setAttribute('name', 'expname[]');
    experimenthidden.setAttribute('value', 'Exp_' + i);
    return experimenthidden;
}

function createUploadFileInput(i) {
    var fileselector = document.createElement('input');
    fileselector.setAttribute('type', 'file');
    fileselector.setAttribute('name', 'file[]');
    fileselector.setAttribute('id', 'file' + i);
    fileselector.setAttribute('onchange', 'dataContentChange()');
    fileselector.setAttribute('accept', '.csv, .tsv');
    return fileselector;
}

function createUploadTextInput(i) {
    var fileselector = document.createElement('textarea');
    fileselector.setAttribute('class', 'form-control');
    fileselector.setAttribute('rows', '10');
    fileselector.setAttribute('name', 'txtquery' + i);
    fileselector.setAttribute('id', 'txtquery' + i);
    fileselector.setAttribute('oninput', 'dataContentChange()');
    return fileselector;
}

function recoverTextData(filenums, data) {
    if (document.getElementsByTagName('textarea').length === filenums)
        for (var i = 0; i < filenums; i++) {
            $("#txtquery" + i).text(data[i]);
        }
}

function onClickAutoLoadButton() {
    $('#autoLoadButton').click(function (e) {
        e.preventDefault();
        var selectedNumber = $('#exp_num').val();
        var rootDir = getRootDir();
        for (var i = 0; i < selectedNumber; i++) {
            (function (index) {
                getDataFromFileByURL(rootDir + "/exp" + index + ".txt", index);
            })(i);
        }
        $('#submit').prop('disabled', false);
    });
}

function onClickAutoLoadDbButton() {
    $('#autoLoadDbButton').click(function (e) {
        e.preventDefault();
        var selectedNumber = $('#exp_num').val();
        var rootDir = getRootDir();
        for (var i = 0; i < selectedNumber; i++) {
            (function (index) {
                getDataFromFileByURL(rootDir + "/expDb" + index + ".txt", index);
            })(i);
        }
        $('#submit').prop('disabled', false);
    });
}

function getRootDir() {
    var rootDirectory = "data";
    var cutoffType = $("input[type='radio'][name='cutofftype']:checked").val();
    if (cutoffType === "CutoffAny") rootDirectory += "/cutoff";
    return rootDirectory;
}

function getDataFromFileByURL(url, index) {
    $.ajax(url)
        .done(function (data) {
            $("#txtquery" + index).val(data);
        })
        .fail(function () {
            index += 1;
            showErrors(["Request fail! (Experiment data field " + index + ")"]);
        });
}


function showErrors(errors) {
    if (errors != null && errors.length > 0) {
        var div = document.createElement('div');
        for (var i = 0; i < errors.length; i++) {
            var p = document.createElement('p');
            p.innerText = errors[i];
            if (i <= errors.length - 2) {
                var hr = document.createElement('hr');
                div.append(p, hr);
            } else
                div.append(p);
        }

        $('#myModal').modal('show');
        $('#myModal .modal-body').html(div);
    }
}

function cutoffChanged() {
    $(function () {
        var initialValues = [
            "0.5",
            "1.0",
            "1.5",
            "2.0",
            "2.5",
            "3.0",
            "3.5"
        ];
        $("#cutoff").autocomplete({
            source: initialValues
        });
    });
}
