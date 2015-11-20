var button = document.querySelector('#locate');

if (button.addEventListener) {
    button.addEventListener('click', getLocation);
} else {
    button.attachEvent('onclick', getLocation);
}

function getLocation() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(function (position) {
            addPlaces(position.coords.latitude, position.coords.longitude);
            //addMap(position.coords.latitude, position.coords.longitude);
        });
    } else {
        console.log('Geolocation unavaliable');
    }
}

function addPlaces(latitude, longitude) {
    fetch('/places/near/' + latitude + '/' + longitude, {
        method: 'get'
    }).then(function (response) {
        return response.json();
    }).then(function (j) {
        if (j.length > 0) {
            var i;
            var places = [];
            for (i = 0; i < j.length; ++i) {
                var latlng = parseLocation(j[i].location);
                var name = j[i].name;
                var slug = j[i].slug;
                places.push([name, slug, latlng[0], latlng[1]]);
            }
            addMap(latitude, longitude, places);
        } else {
            addMap(latitude, longitude);
        }
    }).catch(function (err) {
        console.log(err);
    });
}

function addMap(latitude, longitude, places) {
    //make places null if not supplied
    if (arguments.length == 2) {
        places = null;
    }
    var form = button.parentNode;
    var div = document.createElement('div');
    div.setAttribute('id', 'map');
    form.appendChild(div);
    L.mapbox.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiVlpndW1EYyJ9.aP9fxAqLKh7lj0LpFh5k1w';
    var map = L.mapbox.map('map', 'jonnybarnes.gnoihnim')
        .setView([latitude, longitude], 15)
        .addLayer(L.mapbox.tileLayer('jonnybarnes.gnoihnim', {
            detectRetina: true,
        }));
    var marker = L.marker([latitude, longitude], {
        draggable: true,
    }).addTo(map);
    if (places !== null) {
        //create the <select> element and give it a no location default
        var selectEl = document.createElement('select');
        selectEl.setAttribute('name', 'location');
        var noLocation = document.createElement('option');
        noLocation.setAttribute('selected', 'selected');
        noLocation.setAttribute('value', 'no-location');
        noLocText = document.createTextNode('Select no location');
        noLocation.appendChild(noLocText);
        selectEl.appendChild(noLocation);
        form.insertBefore(selectEl, div);
        //add the places both to the map and <select>
        places.forEach(function (item, index, array) {
            var option = document.createElement('option');
            option.setAttribute('value', item[1]);
            var text = document.createTextNode(item[0]);
            option.appendChild(text);
            option.dataset.latitude = item[2];
            option.dataset.longitude = item[3];
            selectEl.appendChild(option);
            var placeMarker = L.marker([item[2], item[3]], {
                icon: L.mapbox.marker.icon({
                    'marker-size': 'large',
                    'marker-symbol': 'building',
                    'marker-color': '#fa0'
                })
            }).addTo(map);
            var name = 'Name: ' + item[0];
            placeMarker.bindPopup(name, {
                closeButton: true
            });
            placeMarker.on('click', function (e) {
                map.panTo([item[2], item[3]]);
                selectPlace(item[1]);
            });
        });
        //add an event listener
        selectEl.addEventListener('change', function () {
            if (selectEl.value !== 'no-location') {
                var placeLat = selectEl[selectEl.selectedIndex].dataset.latitude;
                var placeLon = selectEl[selectEl.selectedIndex].dataset.longitude;
                map.panTo([placeLat, placeLon]);
            }
        });
    } else {
        var noPlacesText = document.createTextNode('There are no nearby places.');
        var noPlacesPTag = document.createElement('p');
        noPlacesPTag.appendChild(noPlacesText);
        form.insertBefore(noPlacesPTag, div);
    }
    var newLocPTag = document.createElement('p');
    var newLocATag = document.createElement('a');
    var newLocText = document.createTextNode('Create a new place?');
    newLocATag.appendChild(newLocText);
    var url = window.location.href;
    var admin = /admin/.test(url);
    if (admin) {
        newLocATag.setAttribute('href', '/admin/places/new');
    } else {
        newLocATag.setAttribute('href', '/places/new');
    }
    newLocPTag.appendChild(newLocATag);
    form.insertBefore(newLocPTag, div);
}

function parseLocation(point) {
    var re = /\((.*)\)/;
    var resultArray = re.exec(point);
    var location = resultArray[1].split(' ');

    return [location[1], location[0]];
}

function selectPlace(slug) {
    document.querySelector('select [value=' + slug + ']').selected = true;
}
