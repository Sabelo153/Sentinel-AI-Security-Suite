package com.sentinel.guard
import com.google.zxing.BarcodeFormat
import com.google.zxing.qrcode.QRCodeWriter
import org.junit.Assert.*
import org.junit.Test
class QrDecoderTest {
    @Test fun decodesCheckpointWithoutAndroidOrGoogleServices() {
        val matrix=QRCodeWriter().encode("SITE-A-CHECKPOINT-1",BarcodeFormat.QR_CODE,240,240)
        val pixels=IntArray(240*240){i->if(matrix[i%240,i/240])0xff000000.toInt() else 0xffffffff.toInt()}
        assertEquals("SITE-A-CHECKPOINT-1",QrDecoder.decode(pixels,240,240))
        val rotated=IntArray(pixels.size){i->pixels[(239-i%240)*240+i/240]}
        assertEquals("SITE-A-CHECKPOINT-1",QrDecoder.decode(rotated,240,240))
    }
    @Test fun invalidImageDoesNotCrash() { assertNull(QrDecoder.decode(IntArray(100){0xffffffff.toInt()},10,10));assertNull(QrDecoder.decode(IntArray(0),Int.MAX_VALUE,Int.MAX_VALUE)) }
}
