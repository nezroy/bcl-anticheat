/*================= BCL.vg Anticheat =================
 * This program is released under the MIT license.
 * 
 * Libraries used:
 *		libcurl
 *
 * Functions:
 *		Capture a screenshot of the videogame window and POST the file to a web server
 *		Enumerate the module list and POST to a web server
 *
 * Note: Pack this with themida or something before releasing
 * It really needs to be obfuscated.
 *
 *
 * postFile(void *buf, int sz) -- Takes a buffer pointer and size, adds to a post, sends using libcurl.
 * 
 * acTick() is what handles module enumeration, taking the screenshot, etc. It occurs every TICK_INTERVAL seconds(I'd set this to 30 or 60).
 *
 *		Note on module enumeration: If a module hides itself from the PEB(e.g. from a manually mapping loader), it won't show up here.
 *			To get detect this, page-walking can be done. I could prob implement that if I have the free time.
 *
 */

#undef USE_DXCAP

#include <windows.h>
#include <tchar.h>
#include <codecvt>

#include <GdiPlus.h>
#include <Dwmapi.h>

#include <sstream>
#include <iostream>
#include <iomanip>
#include <stdio.h>

#include <stdlib.h>
#include <ctime>

#include <psapi.h>

#include <ole2.h>
#include <olectl.h>

#include <curl/curl.h>

#ifdef USE_DXCAP
#include <d3d9.h>
#include <D3dx9tex.h>
#endif

#include "md5.h"

// GDIPlus global
ULONG_PTR gdiplusToken;

CURL *curl;

std::string hwid;
std::wstring_convert<std::codecvt_utf8<wchar_t>> wtoutf8;

#ifdef USE_DXCAP
LPDIRECT3D9 g_pD3D = NULL;  
LPDIRECT3DDEVICE9 pDevice = NULL;
#endif

using std::cout;
using std::endl;

using namespace Gdiplus; 

#define SS_QUALITY 50

#define TICK_INTERVAL 30
#define SLEEP_INTERVAL 5000
#define REPORT_URL "http://nezroy.com/bclac/report.php"
#define VERSION "0.1.0"

#define ERR_ENUM_PROC 1
#define ERR_NOPROC 2

int GetEncoderClsid(WCHAR *format, CLSID *pClsid)
{
	unsigned int num = 0,  size = 0;
	GetImageEncodersSize(&num, &size);
	if(size == 0) return -1;
	ImageCodecInfo *pImageCodecInfo = (ImageCodecInfo *)(malloc(size));
	if(pImageCodecInfo == NULL) return -1;
	GetImageEncoders(num, size, pImageCodecInfo);
	for (unsigned int j = 0; j < num; ++j) {
		if (wcscmp(pImageCodecInfo[j].MimeType, format) == 0) {
			*pClsid = pImageCodecInfo[j].Clsid;
			free(pImageCodecInfo);
			return j;
		}    
	}
	free(pImageCodecInfo);
	return -1;
}

bool postFile(void *jpgData, int jpgSz, void *modData, int modSz) {
	struct curl_httppost *formpost = NULL;
	struct curl_httppost *lastptr = NULL;

	curl_formadd(&formpost,
		&lastptr,
		CURLFORM_COPYNAME, "o",
		CURLFORM_BUFFER, "o",
		CURLFORM_BUFFERPTR, jpgData,
		CURLFORM_BUFFERLENGTH, jpgSz,
		CURLFORM_END);
	curl_formadd(&formpost,
		&lastptr,
		CURLFORM_COPYNAME, "a",
		CURLFORM_PTRCONTENTS, modData,
		CURLFORM_CONTENTSLENGTH, modSz,
		CURLFORM_END);
	curl_formadd(&formpost,
		&lastptr,
		CURLFORM_COPYNAME, "h",
		CURLFORM_COPYCONTENTS, hwid,
		CURLFORM_END);
	curl_formadd(&formpost,
		&lastptr,
		CURLFORM_COPYNAME, "v",
		CURLFORM_COPYCONTENTS, VERSION,
		CURLFORM_END);

	CURL *curl = curl_easy_init();
	if (!curl) {
		cout << "CURL init failure" << endl;
		return false;
	}

	curl_easy_setopt(curl, CURLOPT_URL, REPORT_URL);
	curl_easy_setopt(curl, CURLOPT_HTTPPOST, formpost);

	cout << "checking in with the server... ";
	CURLcode res = curl_easy_perform(curl);
	cout << endl;

	if (res != CURLE_OK) {
		cout << "Critical failure: " << curl_easy_strerror(res) << endl;

		curl_easy_cleanup(curl);
		curl_formfree(formpost);

		return false;
	}

	curl_easy_cleanup(curl);
	curl_formfree(formpost);

	return true;
}

HWND getWnd() {
	HWND ret;

	// check for BLR
	// Window title "Blacklight: Retribution (32-bit, DX11)"
	// or			"Blacklight: Retribution (32-bit, DX9)"
	if ((ret = FindWindow(NULL,TEXT("Blacklight: Retribution (32-bit, DX11)"))) ||
		(ret = FindWindow(NULL,TEXT("Blacklight: Retribution (32-bit, DX9)")))) {
		#ifdef _DEBUG
		cout << "found window" << ret << endl;
		#endif
		return ret;
	}
	return NULL;
}

// An amalgamy of stackoverflow responses
bool takeShot(HWND hwnd, void *modData, int modSz, HDC hDC) {
	HRESULT hr;
	int winW, winH;

#ifdef USE_DXCAP
	D3DDISPLAYMODE displayMode;
	LPD3DXBUFFER pDestBuf;
	LPDIRECT3DSURFACE9 pd3dsFront = NULL;
	pDevice->GetDisplayMode(0, &displayMode);
	winW = displayMode.Width;
	winH = displayMode.Height;
	pDevice->CreateOffscreenPlainSurface(winW, winH, D3DFMT_A8R8G8B8, D3DPOOL_SCRATCH, &pd3dsFront, NULL);
	hr = pDevice->GetFrontBufferData(0, pd3dsFront);
	if (FAILED(hr)) {
		cout << "failed getfrontbuffer: " << hr << endl;
		pd3dsFront->Release();
		return false;
	}
	// can't just getdc the surface because it doesn't work with A8R8G8B8, which is the only FMT that works with front buffer
	// D3DXSaveSurfaceToFile(L"pickture.bmp", D3DXIFF_BMP, pd3dsFront, NULL, NULL);
	D3DXSaveSurfaceToFileInMemory(&pDestBuf, D3DXIFF_BMP, pd3dsFront, NULL, NULL);
	int bufSz = pDestBuf->GetBufferSize();
	LPBYTE pData = (LPBYTE)malloc(bufSz);
	memcpy(pData, (LPBYTE)pDestBuf->GetBufferPointer(), bufSz);
	pDestBuf->Release();
	pd3dsFront->Release();
	BITMAPINFO bmi;
	ZeroMemory(&bmi, sizeof(bmi));
	bmi.bmiHeader.biSize = sizeof(BITMAPINFOHEADER);
	bmi.bmiHeader.biWidth = winW;
	bmi.bmiHeader.biHeight = winH;
	bmi.bmiHeader.biPlanes = 1;
	bmi.bmiHeader.biCompression = BI_RGB;
	bmi.bmiHeader.biBitCount = 32;
	Gdiplus::Bitmap gdiBitmapOrig(&bmi, pData + 54); // skip the 54 byte header
#else	
	WINDOWINFO wi;
	// GetWindowInfo(hwnd, &wi);
	GetWindowInfo(GetDesktopWindow(), &wi);
	winW = wi.rcClient.right - wi.rcClient.left;
	winH = wi.rcClient.bottom - wi.rcClient.top;
	DeleteDC(hDC);
	hDC = GetDC(GetDesktopWindow());
	HDC hdcMemory = CreateCompatibleDC(hDC);
	HBITMAP hBitmap = CreateCompatibleBitmap(hDC, winW, winH);
	if (!hBitmap)
		cout << "Bitmap failed" << endl;
	HBITMAP hBitmapOld = (HBITMAP)SelectObject(hdcMemory, hBitmap);
	// BOOL bbret = BitBlt(hdcMemory, 0, 0, winW, winH, hDC, 0, 0, SRCCOPY | CAPTUREBLT);
	BOOL bbret = BitBlt(hdcMemory, 0, 0, winW, winH, hDC, wi.rcClient.left, wi.rcClient.top, SRCCOPY | CAPTUREBLT);
	if (!bbret)
		cout << "bb failed err: " << GetLastError() << endl;
	hBitmap = (HBITMAP)SelectObject(hdcMemory, hBitmapOld);
	if (!hBitmap)
		cout << "selectobj failed" << endl;

	Gdiplus::Bitmap gdiBitmapOrig (hBitmap, (HPALETTE)NULL);	
	DeleteDC(hdcMemory);
#endif
	DeleteDC(hDC);

	// scale the image by 50% then convert to jpg in memory buffer
	// http://stackoverflow.com/questions/5610075/taking-a-jpeg-encoded-screenshot-to-a-buffer-using-gdi-and-c
	// TODO: ERROR CHECKING
	int newW = winW / 2;
	int newH = winH / 2;
	Gdiplus::Bitmap gdiBitmap(newW, newH, gdiBitmapOrig.GetPixelFormat());
	Gdiplus::Graphics graphics(&gdiBitmap);
	graphics.DrawImage(&gdiBitmapOrig, 0, 0, newW, newH);

	IStream *pBMPStream = NULL;																		// Stream where we store our data during transfer

	// Class ID of the GDI+ JPEG class
	CLSID jpegCLSID;
	GetEncoderClsid(L"image/jpeg", &jpegCLSID);

	// Parameters to pass the GDI+ JPEG encoder
	EncoderParameters encoderParams;
	encoderParams.Count = 1;
	encoderParams.Parameter[0].NumberOfValues = 1;
	encoderParams.Parameter[0].Guid = EncoderQuality;
	encoderParams.Parameter[0].Type = EncoderParameterValueTypeLong;

	unsigned long tempQual = SS_QUALITY;															// Have to store it on the stack temporarily due to reference required below...
	encoderParams.Parameter[0].Value = &tempQual;													// JPEG compression quality. Def: 50
	unsigned long bytesRead = 0;																	// Amount of bytes read from the stream

	// Stream positioning data (windows aliases for a QWORD struct, so {} init)
	LARGE_INTEGER streamStart = {};
	ULARGE_INTEGER streamPos = {};
	STATSTG stg = {};																				// Stores stream data(size, etc.)

	hr = CreateStreamOnHGlobal(NULL, TRUE, &pBMPStream);											// So GDI C++ uses "handles", we need to create a stream on some handle we don't care about to use it(it just stores our data termporarily)
	hr = gdiBitmap.Save(pBMPStream, &jpegCLSID, &encoderParams) == 0 ? S_OK : E_FAIL;				// Write bitmap to the stream, GDI will convert to jpeg for us. Tenary operator for result as normal returns 0
	hr = pBMPStream->Seek(streamStart, STREAM_SEEK_SET, &streamPos);								// Get the beginning
	hr = pBMPStream->Stat(&stg, STATFLAG_NONAME);													// Retrives size and other properties of data in stream
	BYTE *jpgBuf = new BYTE[stg.cbSize.LowPart];													// Make the jpg buffer based on the low dword of the size(THIS ASSUMES 32BITS IS LARGE ENOUGH)
	hr = (jpgBuf==NULL) ? E_OUTOFMEMORY : S_OK;														// Buffer should exist.
	hr = pBMPStream->Read(jpgBuf, stg.cbSize.LowPart, &bytesRead);									// ACTUALLY COPIES THE STREAM DATA INTO THE BUFFER

	// At this point, we have a jpg buffer in jpgBuf of size stg.cbSize.LowPart
	#ifdef _DEBUG
	cout << "bitmap done with HR " << hr << " for w: " << newW << "h: " << newH << " Val: " << endl;
	#endif
	bool happyPost = postFile(jpgBuf, stg.cbSize.LowPart, modData, modSz);

	// Free resources
	free(jpgBuf);
	if (pBMPStream) pBMPStream->Release();
#ifdef USE_DXCAP
	free(pData);
#endif

	return happyPost;
}

// Notes about this tick system:
//		Will block while data is being sent to server, could take a long time depending on the amount of data sent
bool acTick() {
	#ifdef _DEBUG
	cout << "Tick!" << endl;
	#endif

	HDC	hDC = NULL;
	HWND curWnd = getWnd();
	if (NULL == curWnd) return false;

	// check for D(evice)C(ontext) -- May not exist if BLR window was just created?
	if ((hDC = GetDC(curWnd))) {
		#ifdef _DEBUG
		cout << "Got DC " << hDC << endl;
		#endif
	}
	else {
		return false;
	}

	// dump module list to a wstringstream, windows types ahoy -- ala MSDN(with some helpful naming)
	HMODULE hMods[1024];										// Array containing windows handles to modules
    DWORD szMods;												// Size in bytes of every module.
	std::wstringstream wNamesOut;
	DWORD curPID;

	#ifdef _DEBUG
	cout << "starting module listing" << endl;
	#endif
	GetWindowThreadProcessId(curWnd, &curPID);
	HANDLE hProcess = OpenProcess( PROCESS_QUERY_INFORMATION | PROCESS_VM_READ, FALSE, curPID );
	if (hProcess == NULL) {
		cout << "ERR: " << ERR_NOPROC << ":" << GetLastError() << endl;
		return false;
	}

	if ( EnumProcessModules(hProcess, hMods, sizeof(hMods), &szMods)) {	
		// HMODULE is just a handle/pointer, so it's almost always 4, but for the sake of compatibility...
		for ( u_int i = 0; i < (szMods / sizeof(HMODULE)); i++ ) {
			WCHAR szModName[MAX_PATH];

			// Get the full path to the module's file.
			if ( GetModuleFileNameEx( hProcess, hMods[i], szModName, sizeof(szModName) / sizeof(TCHAR))) {
				// Print the module name and handle value.
				// The server will expect modpath modaddr,modpath modaddr,modpath modaddr
				// This will allow it to be treated as a php array using explode first by commas then by spaces
				wNamesOut << szModName << L" ";
				wNamesOut << hMods[i] << L",";
			}
		}
	}
	else {
		cout << "ERR: " << ERR_ENUM_PROC << endl;
		return false;
	}

	CloseHandle( hProcess );

	// Important memory management note! We have to store .str() in a local instead of doing .c_str directly, as that data is only temporary!
	//std::wstring tempModData = wNamesOut.str();
	//const wchar_t *modData = tempModData.c_str();
	//int modSz = wcslen(modData) * sizeof(wchar_t);
	// convert wchar to utf8 for the form upload
	std::string tempModData = wtoutf8.to_bytes(wNamesOut.str());
	const char *modData = tempModData.c_str();
	int modSz = tempModData.length() + 1;

	// Take the screenshot and post it, along with the module info just collected, to the site PHP
	#ifdef _DEBUG
	cout << "starting screenshot" << endl;
	#endif
	return takeShot(curWnd, (char *)modData, modSz, hDC);
}

void gethwid() {
	HKEY hKey = 0;
	char buf[255];
	DWORD dwBufSize = sizeof(buf);

	if(RegOpenKeyExA(HKEY_LOCAL_MACHINE, "Software\\Microsoft\\Cryptography", 0, KEY_QUERY_VALUE | KEY_WOW64_64KEY, &hKey) == ERROR_SUCCESS) {
		if(RegQueryValueExA(hKey, "MachineGuid", 0, 0, (BYTE*)buf, &dwBufSize) == ERROR_SUCCESS) {
			hwid = md5(std::string(buf));
		}
		else {
			cout << "error getting hwid\n";
		}
	}
	else {
		cout << "error getting hwid\n";
	}

	// put the hwid in the clipboard
	// http://stackoverflow.com/questions/3177319/set-global-clipboard-text-in-windows-native-c
	if (OpenClipboard(NULL)) {
		HGLOBAL clipbuffer;
		char *buffer;
		EmptyClipboard();
		clipbuffer = GlobalAlloc(GMEM_DDESHARE, hwid.length() + 1);
		buffer = (char*)GlobalLock(clipbuffer);
		strcpy_s(buffer, hwid.length() + 1, hwid.c_str());
		GlobalUnlock(clipbuffer);
		SetClipboardData(CF_TEXT, clipbuffer);
		CloseClipboard();
	}
}

int main(int argc, CHAR* argv[]) {

	cout << "BCL.vg Anticheat v" << VERSION << endl;


	WINDOWINFO wi;
	GetWindowInfo(GetDesktopWindow(), &wi);
	HDC hDC = GetDC(GetDesktopWindow());
	cout << "by window: " << wi.rcClient.right - wi.rcClient.left << " : " << wi.rcClient.bottom - wi.rcClient.top << endl;
	cout << "by caps: " << GetDeviceCaps(hDC, HORZRES) << " : " << GetDeviceCaps(hDC, VERTRES) << endl;
	DeleteDC(hDC);



	curl_global_init(CURL_GLOBAL_ALL);
	GdiplusStartupInput gdiplusStartupInput;
	GdiplusStartup(&gdiplusToken, &gdiplusStartupInput, NULL);
	DwmEnableComposition(DWM_EC_DISABLECOMPOSITION); // disable composition, if possible, for dx full screen shots

	gethwid();
	cout << "Your HWID is: " << hwid << endl;
	cout << "(this value has been placed in the windows clipboard)" << endl;

	#ifdef USE_DXCAP
	// create d3d device in global pDevice for screen captures
	g_pD3D = Direct3DCreate9(D3D_SDK_VERSION);
	if (NULL == g_pD3D) {
		cout << "failed D3DCreate" << endl;
	}
	D3DPRESENT_PARAMETERS d3dpp; 
	ZeroMemory( &d3dpp, sizeof(d3dpp) );
	d3dpp.Windowed   = TRUE;
	d3dpp.SwapEffect = D3DSWAPEFFECT_DISCARD;
	HRESULT hr = g_pD3D->CreateDevice( D3DADAPTER_DEFAULT, D3DDEVTYPE_HAL, GetConsoleWindow(), D3DCREATE_SOFTWARE_VERTEXPROCESSING, &d3dpp, &pDevice );
	if (FAILED(hr)) {
		cout << "failed D3DCreateDevice: " << hr << endl;
	}
	#endif

	
	// main execution loop
	time_t lastExecTime = 0;
	while (true) {
		time_t curTime = time(NULL);					// Get current time
		if (curTime - lastExecTime >= TICK_INTERVAL) {	// Enough time has passed to run a new tick
			if (acTick()) lastExecTime = curTime;		// Store ticktime on successful ticks
		}
		Sleep(SLEEP_INTERVAL);
	}


	// clean up
	#ifdef USE_DXCAP
	pDevice->Release();
	pDevice = NULL;
	g_pD3D->Release();
	g_pD3D = NULL;
	#endif
	curl_global_cleanup();
	GdiplusShutdown(gdiplusToken);

	system("pause");

	return 0;
}
