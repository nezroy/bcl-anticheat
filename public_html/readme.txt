Spent most of my time fiddling with alternate methods to grab screenshots once I discovered that full-screen DX11 and full-screen DX9 with Aero/desktop composition does not screenshot (just get black/blank buffer). Tried getting shots with DirectX GetFrontBuffer, which works but has the same limitation (see bclac_dxwnd.cpp for a copy of that version). Research on the net suggests the only way to work around this is to hook the D3D Present/CreateDevice calls and do work in there to setup hooks into every running DX app. Apparently this is how FRAPS does it.

As a stop-gap, I disable windows composition, which works to at least get screens on DX9 full-screen for Win7 and Vista (it probably would also work on Win8 if you could disable composition on Win8, but you can't). Still don't get usable screens for DX11 full-screen or DX9 full-screen on Win8. I updated the bitblt method to grab the entire desktop hdc for this scenario as well rather than only the BLR window handle, since otherwise it still fails to get the DX9 full-screen even under these limited conditions.

I reduced the image capture to 50% original size before converting to JPG, since that seems to be plenty to get usable info while dropping file sizes. I also feel slightly less bad about grabbing entire desktops when I can't actually ready anything on it.

Changed the execution loop somewhat. Instead of sleeping inside various calls, always returns out to the main execution loop on any failure in acTick or its callees. Then sleep for 5s (bumped from 5ms) there and try again. Once a successul tick happens it will then wait the full tick duration.

I consolidated the HTTP POST of screenshot and mod data into a single network call to reduce overhead. This is accomplished through some really lame nesting of the mod data into the takeshot function. Also convert the mod data from wchar to UTF8 to reduce size and because PHP doesn't know what to do with whcar anyway.

Updated to static linking/static libcurl for deployment reasons (no need for libcurl/zlib dlls, msvcr version pain, etc.)

Updated project/solution files to VS2012 express since that's what I've got.

Added a super-simple HWID (md5 hashed MachineGuid) and display it/put it in clipboard on app launch. Also check with the backend to see if the HWID has been registered to a player name yet. Obviously not hard to spoof but this whole thing is about 50% psychological deterrent anyway.

On the backend, not much going on. Just some dead simple PHP to register an HWID to username and to file a report to an SQLite DB while squirreling off the screenshot into a directory by HWID/timestamp. Added a basic auth restriction on the API calls; not hard to pull these strings out of the exe or off the network but it should discourage people from just randomly poking at the API endpoints at least. Much fanciness could be added here ulimately but it's not that big of a tournament :) 
