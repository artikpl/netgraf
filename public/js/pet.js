function PetForm(form){
    this.form = form;
    this.form.find('[name=remove-tag],[name=remove-photo]').click(function(e){
        $(e.target).parents('p').eq(0).remove();
    });

    this.form.find('[name=remove-pet]').click(function(){
        if(confirm("Czy na pewno chcesz usunąć zwierzaka?")){
            var id = this.form.find('input[name=id]').val()?.trim();
            $.ajax({
                url : '/pets/'+id,
                method : 'delete',
                success:function(){
                    window.top.location.href = '/pets';
                },
                data:JSON.stringify({
                    _token: this.form.find('input[name=_token]').val()?.trim(),
                }),
                contentType:'application/json'
            });
        }
    }.bind(this));
    this.tagsNode = form.find('div.tags-node');
    this.photosNode = form.find('div.photos-node');

    this.save = function(){
        var inputs = this.form.find(':input');
        var name = inputs.filter('[name=name]').val().trim();
        if(name===''){
            throw "Podaj nazwę zwierzęcia";
        }
        var status = inputs.filter('[name=status]').val()?.trim();

        if((status??'') === ''){
            throw "Wskaż status";
        }

        var catId = inputs.filter('[name="category.id"]').val().trim();
        var catName = inputs.filter('[name="category.name"]').val().trim();
        var category = null;
        if(catId === "" || catId === "0"){
            if(catName !== ""){
                throw "Podaj ID kategorii";
            }
        }else if(catId.match(/[0-9]+/g)[0] === catId && catId>0){
            if(catName === ""){
                throw "Podaj nazwę kategorii";
            }
            category = {
                name : catName,
                id: catId*1
            };
        }else{
            throw "ID kategorii jest nieprawidłowe";
        }

        var inputsArr = this.form.serializeArray();
        var tags = {};
        var photos = [];
        var token = null;
        for(var i of inputsArr){
            if(i.name === '_token'){
                token = i.value;
            }
            var t = i.name.match(/tags\[([0-9\.]+)\]\[(id|name)\]/);
            if(t && t.length>0){
                if(!tags.hasOwnProperty(t[1])){
                    tags[t[1]] = {};
                }
                tags[t[1]][t[2]] = t[2] === 'id' ? i.value*1 : i.value;
            }
            var p = i.name.match(/photos\[([0-9\.]+)\]/);
            if(p && p.length>0){
                photos.push(i.value);
            }
        }
        var d = {
            _token : token,
            name : name,
            category : category,
            status : {
                code : status
            },
            tags: Object.values(tags),
            photoUrls:photos
        };
        var id = inputs.filter('input[name=id]').val()?.trim();
        $.ajax({
            url : '/pets' + (id === undefined ? '' : '/'+id),
            method : id === undefined ? 'post' : 'patch',
            success:this.petSaved.bind(this),
            error:this.petUnsaved.bind(this),
            data:JSON.stringify(d),
            contentType:'application/json'
        });
    }
    this.petSaved = function(data){
        if(data && data.pet && data.pet.id>0){
            window.top.location.href = data.url;
        }
    }
    this.petUnsaved = function(xhr){
        var e = xhr.responseJSON?.error;
        console.log(e);
        e = typeof e === 'string' ? e : 'Nieznany błąd';
        alert(e);
    }
    this.addPhoto = function(){
        var url = this.form.find('[name="photo.url"]').val().trim();
        if(url === ''){
            throw "Podaj url zdjęcia";
        }
        this.form.find('[name="photo.url"]').val('');
        var p = $('<p>').text(url).appendTo(this.photosNode);
        var id = Math.random();
        p.append(
            $('<input>').attr({
                type:'hidden',
                name:'photos['+id+']',
                value:url
            }),
            $('<button type="button" class="remove-photo">').text("Usuń").click(function(p){
                p.remove();
            }.bind(this,p))
        );
    }
    this.addTag = function(){
        var inputs = this.form.find(':input');
        var tagId = inputs.filter('[name="tag.id"]').val().trim();
        var tagName = inputs.filter('[name="tag.name"]').val().trim();
        if((tagId.match(/[0-9]+/g)??[])[0] !== tagId || tagId<1){
            throw "Podaj prawidłowe ID tagu";
        }
        if(tagName === ""){
            throw "Podaj wartość tagu";
        }
        inputs.filter('[name="tag.id"]').val('');
        inputs.filter('[name="tag.name"]').val('');
        var p = $('<p>').text('#'+tagId+", "+tagName).appendTo(this.tagsNode);
        var id = Math.random();
        p.append(
            $('<input>').attr({
                type:'hidden',
                name:'tags['+id+'][id]',
                value:tagId
            }),
            $('<input>').attr({
                type:'hidden',
                name:'tags['+id+'][name]',
                value:tagName
            }),
            $('<button type="button" name="remove-tag">').text("Usuń").click(function(p){
                p.remove();
            }.bind(this,p))
        );

    }

    this.form.submit(function(e){
        //e.preventDefault();
        return false;
    });

    this.proceed = function(fn){
        try {
            fn.apply(this);
        }catch(e){
            alert(e);
        }
    }
    this.form.find('button[name="add.tag"]').click(this.proceed.bind(this,this.addTag));
    this.form.find('button[name="save.pet"]').click(this.proceed.bind(this,this.save));
    this.form.find('button[name="add.photo"]').click(this.proceed.bind(this,this.addPhoto));
}


$(document).ready(function() {
    var forms = $('form[name=pet]');
    if(forms.length>0){
        new PetForm(forms.eq(0));
    }
});
