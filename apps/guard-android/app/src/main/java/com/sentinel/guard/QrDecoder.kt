package com.sentinel.guard

import com.google.zxing.BinaryBitmap
import com.google.zxing.DecodeHintType
import com.google.zxing.RGBLuminanceSource
import com.google.zxing.ReaderException
import com.google.zxing.common.HybridBinarizer
import com.google.zxing.qrcode.QRCodeReader

/** Standalone ZXing core: no Play services, account, model download or network call. */
object QrDecoder {
    fun decode(pixels: IntArray, width: Int, height: Int): String? {
        if(width<1 || height<1 || width.toLong()*height>4_000_000 || pixels.size.toLong()!=width.toLong()*height)return null
        val bitmap=BinaryBitmap(HybridBinarizer(RGBLuminanceSource(width,height,pixels)))
        return try { QRCodeReader().decode(bitmap,mapOf(DecodeHintType.TRY_HARDER to true,DecodeHintType.CHARACTER_SET to "UTF-8")).text }
        catch(_:ReaderException) { null }
    }
}
