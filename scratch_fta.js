import fs from "fs";

const buf = fs.readFileSync("c:/nutrisurvey2007/indo.fta");
const rec0 = buf.slice(0, 1156);

// Function to decode Pascal 6-byte Real (Real48)
function readReal48(b, offset) {
    const exp = b[offset];
    if (exp === 0) return 0;

    // Mantissa is 39 bits + 1 implied bit, plus 1 sign bit
    // Bytes: [exponent, mantissa0, mantissa1, mantissa2, mantissa3, mantissa4]
    // In Pascal Real48: byte 0 is exponent (biased by 129), byte 5 contains sign bit at msb
    const sign = b[offset + 5] & 0x80 ? -1 : 1;
    let mantissa = 0;

    // Most significant byte of mantissa (with sign bit cleared and implied 1 bit set)
    mantissa += b[offset + 5] | 0x80;
    mantissa = mantissa * 256 + b[offset + 4];
    mantissa = mantissa * 256 + b[offset + 3];
    mantissa = mantissa * 256 + b[offset + 2];
    mantissa = mantissa * 256 + b[offset + 1];

    // mantissa has 40 bits total (value between 0.5 and 1.0 or 1.0 and 2.0)
    const normMantissa = mantissa / Math.pow(2, 40);
    const actualExp = exp - 129;
    return sign * normMantissa * Math.pow(2, actualExp + 1);
}

// Function to decode 4-byte IEEE Float (Single)
function readFloat32(b, offset) {
    return b.readFloatLE(offset);
}

// Function to decode 8-byte IEEE Double
function readDouble64(b, offset) {
    return b.readDoubleLE(offset);
}

console.log("Checking offsets 60 to 200 with Float32:");
for (let i = 60; i < 200; i += 4) {
    const val = readFloat32(rec0, i);
    if (!isNaN(val) && val > -1000 && val < 5000) {
        console.log(`Offset ${i} (Single): ${val.toFixed(2)}`);
    }
}

console.log("\nChecking offsets 60 to 200 with Real48 (6-byte Pascal):");
for (let i = 60; i < 200; i += 6) {
    const val = readReal48(rec0, i);
    if (!isNaN(val) && val > -1000 && val < 5000) {
        console.log(`Offset ${i} (Real48): ${val.toFixed(2)}`);
    }
}
