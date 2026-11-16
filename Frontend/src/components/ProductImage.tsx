import type { ImgHTMLAttributes } from 'react'
import { imageSrcSetUrl, imageUrl } from '../api/client'

interface ProductImageProps extends Omit<ImgHTMLAttributes<HTMLImageElement>, 'src'> {
  path?: string | null
  produitId: number
  imageSrcSet?: string
  pictureClassName?: string
}

export function ProductImage({
  path,
  produitId,
  imageSrcSet,
  pictureClassName,
  sizes,
  ...imageProps
}: ProductImageProps) {
  return (
    <picture className={pictureClassName ?? 'block'}>
      {imageSrcSet && <source type="image/webp" srcSet={imageSrcSetUrl(imageSrcSet)} sizes={sizes} />}
      <img {...imageProps} src={imageUrl(path, produitId)} sizes={sizes} />
    </picture>
  )
}
