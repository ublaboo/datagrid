Table of contents

- [Assets](#assets)
	- [NPM](#npm)
	- [Example html when not using NPM](#example-html-when-not-using-npm)

# Assets

DataGrid needs for its precise functionality some third party scripts and styles. Install all required assets with NPM.

**CSS (external)**

- bootstrap
- bootstrap datepicker
- bootstrap select

**CSS**

- datagrid.css
- datagrid-spinners.css

**JS (external)**

- jquery
- nette forms
- nette ajax / naja
- bootstrap
- bootstrap datepicker
- bootstrap select

**JS**

- datagrid.js
- datagrid-instant-url-refresh.js
- datagrid-spinners.js

**Icons**

You will probably want to use some icon font, but that is in your command.
On this project website we use font awesome (you can change the icon prefix by setting new value to static property `DataGrid::$iconPrefix = 'fa fa-';`).

**Spinners**

As you can see, there is also a `datagrid-spinners.js` script in a datagrid repository. If you include this file within you project layout, there are some actions, that will show spinner/some other animation when waiting for ajax response. Actions, that has somehow animated spinner:

- Group actions
- Pagination
- Changing items per page
- Toggling item detail - loading the detail for the first time

## NPM

```
npm install --save ublaboo-datagrid
```

package.json:

```json
{
	"dependencies": {
		"bootstrap-datepicker": "^1.9",
		"bootstrap-select": "^1.13",
		"bootstrap": "^4.4.1",
		"happy-inputs": "^2.0",
		"jquery": "^3.4.1",
		"jquery-ui-sortable": "^1.0",
		"nette-forms": "^3.0",
		"nette.ajax.js": "^2.3",
		"popper.js": "^1.14.7",
		"ublaboo-datagrid": "^6.2"
	}
}
```

## Example html when not using NPM

```html
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/happy-inputs@2.0.4/src/happy.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ublaboo-datagrid@6.2.13/assets/datagrid.css">

	<!-- Use this css for ajax spinners -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ublaboo-datagrid@6.2.13/assets/datagrid-spinners.css">

	<!-- Include this css when using FilterMultiSelect (silviomoreto.github.io/bootstrap-select) -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.15/dist/css/bootstrap-select.css">

	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
</head>

<body>
	<script src="https://cdn.jsdelivr.net/npm/happy-inputs@2.1.0/src/nomodule-es5-fallback.js"></script>
	<script>
		var happy = new Happy;

		happy.init();
	</script>
	<script src="https://cdn.jsdelivr.net/npm/jquery@3.4.1/dist/jquery.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/js/bootstrap-datepicker.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-ui-sortable@1.0.0/jquery-ui.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/nette.ajax.js@2.3.0/nette.ajax.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/ublaboo-datagrid@6.2.13/assets/datagrid.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/nette-forms@3.0.4/src/assets/netteForms.min.js"></script>

	<!-- It is recommended to include this JS file with just a few bits. It refreshes URL on non ajax request -->
	<script src="https://cdn.jsdelivr.net/npm/ublaboo-datagrid@6.2.13/assets/datagrid-instant-url-refresh.js"></script>

	<!-- Use this little extension for ajax spinners -->
	<script src="https://cdn.jsdelivr.net/npm/ublaboo-datagrid@6.2.13/assets/datagrid-spinners.js"></script>

	<!-- Include bootstrap-select.js when using FilterMultiSelect (silviomoreto.github.io/bootstrap-select) -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.15/dist/js/bootstrap-select.js"></script>
</body>
</html>
```
