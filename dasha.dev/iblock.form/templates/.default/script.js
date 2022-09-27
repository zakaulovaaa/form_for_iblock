(function () {
    'use strict'
    BX.namespace('BX.Iblock.Form');
    BX.Iblock.Form = {
        ids: {
            block_form: 'iblock-form'
        },

        init: function (parameters) {
            this.result = parameters.result || {};
            this.ajaxUrl = parameters.ajaxUrl || '';
        },

        add: function (element) {
            let data = {}
            let field = $(element).closest('form')
            let form = field.serializeArray()

            $.each(form, BX.delegate(function (RowIndex, RowValue) {
                let name = RowValue.name
                data[name] = RowValue.value
            }, this))

            data.action = 'add'
            data.sessid = BX.bitrix_sessid()
            this.sendRequest(data)
        },

        sendRequest: function (data) {
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.ajaxUrl,
                data: data,
                async: true,
                timeout: 60,
                onsuccess: BX.delegate(function (result) {
                    if (result.STATUS === 'SUCCESS') {
                        alert(result.MESSAGE);
                    }
                    if (result.STATUS === 'ERROR') {
                        alert(result.MESSAGE);
                    }
                }, this), onfailure: BX.delegate(function () {
                }, this),
            })
        }
    }
})();
