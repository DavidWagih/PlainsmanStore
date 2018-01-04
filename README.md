Promimity Checker for Plainsman Store

I am a little confused about how to actually call this from PHP on the server side since it is actually operating as a javascript function that shows alerts on the client side. Do you have any suggestions? The idea is for the webstore to know if visitors are within a geographic area to decide what prices to show them.

Like this:

if(VisitorWithinKm(150)) {
  define('SHOWPRICES', true);
} else {
  define('SHOWPRICES', false);
}
